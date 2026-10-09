<?php

namespace Modules\Notification\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Modules\Notification\Jobs\BroadcastNotificationJob;
use Modules\Notification\Models\PushNotification;
use Mrj\Foundation\Services\FileManagerService;

/**
 * Persist a push-notification record and queue the delivery (in-app + push).
 *
 * Usage (from a controller with validated data):
 *   app(SendPushNotificationAction::class)->execute($request->validated());
 */
final readonly class SendPushNotificationAction
{
    public const string TYPE = 'announcement';

    /**
     * Sends to one user (`specific`), every active user (`all`) or the active users holding a
     * role (`role`). The image goes out as the push's picture and the URL in its data.
     *
     * @param  array{
     *     title: string,
     *     body: string,
     *     recipient_type?: string|null,
     *     user_id?: int|string|null,
     *     recipient_role?: string|null,
     *     url?: string|null,
     *     description?: string|null,
     *     image?: UploadedFile|null,
     *     data?: array<string,mixed>|null,
     * }  $payload
     */
    public function execute(array $payload): PushNotification
    {
        $type = $payload['recipient_type'] ?? 'specific';

        $notification = new PushNotification;
        $notification->title = $payload['title'];
        $notification->body = $payload['body'];
        $notification->recipient_type = $type;
        $notification->user_id = $type === 'specific' ? (int) $payload['user_id'] : null;
        $notification->recipient_role = $type === 'role' ? ($payload['recipient_role'] ?? null) : null;
        $notification->url = $payload['url'] ?? null;
        $notification->description = $payload['description'] ?? null;

        if (($payload['image'] ?? null) instanceof UploadedFile) {
            $notification->image = $payload['image'];
        }

        $notification->result = ['recipients' => self::recipients($type, $notification->user_id, $notification->recipient_role)->count()];
        $notification->save();

        $raw = $notification->getRawOriginal('image');

        BroadcastNotificationJob::dispatch(
            title: $notification->title,
            body: $notification->body,
            data: array_filter([
                ...($payload['data'] ?? []),
                'type' => self::TYPE,
                'push_notification_id' => $notification->id,
                'url' => $notification->url,
            ], static fn ($v) => $v !== null && $v !== ''),
            userId: $type === 'specific' ? (int) $notification->user_id : null,
            channels: ['database', 'fcm'],
            role: $notification->recipient_role,
            image: $raw ? FileManagerService::getImage($raw) : null,
        );

        return $notification;
    }

    /**
     * Who a push goes to: active accounts only (deactivated and deleted ones are skipped).
     *
     * @return Builder<User>
     */
    public static function recipients(string $type, ?int $userId = null, ?string $role = null): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->when($type === 'specific', fn (Builder $q) => $q->whereKey($userId))
            ->when($type === 'role' && $role !== null, fn (Builder $q) => $q->whereHas('roles', fn (Builder $r) => $r->where('name', $role)));
    }
}
