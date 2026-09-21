<?php

namespace Modules\Notification\Actions;

use Illuminate\Http\UploadedFile;
use Modules\Notification\Jobs\BroadcastNotificationJob;
use Modules\Notification\Models\PushNotification;

/**
 * Persist a push-notification record and queue the FCM dispatch.
 *
 * Usage (from a controller with validated data):
 *   app(SendPushNotificationAction::class)->execute($request->validated());
 */
final readonly class SendPushNotificationAction
{
    /**
     * Always targets a specific user; delivers in-app (database) + push (fcm).
     *
     * @param  array{
     *     title: string,
     *     body: string,
     *     user_id: int,
     *     url?: string|null,
     *     description?: string|null,
     *     image?: UploadedFile|null,
     *     data?: array<string,mixed>|null,
     * }  $payload
     */
    public function execute(array $payload): PushNotification
    {
        $notification = new PushNotification;
        $notification->title = $payload['title'];
        $notification->body = $payload['body'];
        $notification->recipient_type = 'specific';
        $notification->user_id = (int) $payload['user_id'];
        $notification->url = $payload['url'] ?? null;
        $notification->description = $payload['description'] ?? null;

        if (($payload['image'] ?? null) instanceof UploadedFile) {
            $notification->image = $payload['image'];
        }

        $notification->save();

        BroadcastNotificationJob::dispatch(
            title: $notification->title,
            body: $notification->body,
            data: $payload['data'] ?? null,
            userId: (int) $notification->user_id,
            channels: ['database', 'fcm'],
        );

        return $notification;
    }
}
