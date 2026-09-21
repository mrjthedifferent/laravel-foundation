<?php

namespace Modules\Notification\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Notifications\AppNotification;

/**
 * Processes a single chunk of user IDs for a broadcast push notification.
 * Dispatched by BroadcastNotificationJob.
 */
class ChunkBroadcastNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param  array<int, int>  $userIds
     * @param  array<string, mixed>|null  $data
     * @param  list<string>  $channels
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly ?array $data = null,
        public readonly array $userIds = [],
        public readonly array $channels = ['fcm'],
    ) {
        $this->onQueue(config('notification.queue', 'notifications'));
        $this->onConnection(config('notification.queue_connection'));
    }

    public function handle(): void
    {
        $notification = new AppNotification(
            title: $this->title,
            body: $this->body,
            data: $this->data ?? [],
            channels: $this->channels,
        );

        User::query()
            ->whereIn('id', $this->userIds)
            ->select(['id'])
            ->with('firebaseTokens:id,user_id,token')
            ->get()
            ->each(function (User $user) use ($notification) {
                try {
                    $user->notify($notification);
                } catch (\Exception $e) {
                    Log::error('Chunk broadcast failed for user '.$user->id.': '.$e->getMessage());
                }
            });
    }
}
