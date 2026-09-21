<?php

namespace Modules\Notification\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Notifications\AppNotification;

/**
 * Dispatches push notifications to one user or (in chunks) to all users.
 *
 * For broadcast (userId = null) each chunk of users is handed off to a
 * separate queued job so no single job processes thousands of users.
 */
class BroadcastNotificationJob implements ShouldQueue
{
    use Queueable;

    /** How many users are processed in a single chunk job. */
    private const CHUNK_SIZE = 100;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param  array<string, mixed>|null  $data
     * @param  list<string>  $channels
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly ?array $data = null,
        public readonly ?int $userId = null,
        public readonly array $channels = ['fcm'],
    ) {
        $this->onQueue(config('notification.queue', 'notifications'));
        $this->onConnection(config('notification.queue_connection'));
    }

    public function handle(): void
    {
        if ($this->userId === null) {
            $this->broadcastToAll();
        } else {
            $this->sendToUser($this->userId);
        }
    }

    // -------------------------------------------------------------------------

    private function broadcastToAll(): void
    {
        User::query()
            ->select('id')
            ->chunk(self::CHUNK_SIZE, function ($users) {
                ChunkBroadcastNotificationJob::dispatch(
                    title: $this->title,
                    body: $this->body,
                    data: $this->data,
                    userIds: $users->pluck('id')->all(),
                    channels: $this->channels,
                );
            });
    }

    private function sendToUser(int $userId): void
    {
        $user = User::find($userId);

        if ($user === null) {
            Log::warning('BroadcastNotificationJob: user not found', ['user_id' => $userId]);

            return;
        }

        try {
            $user->notify(new AppNotification(
                title: $this->title,
                body: $this->body,
                data: $this->data ?? [],
                channels: $this->channels,
            ));
        } catch (\Exception $e) {
            Log::error('Notification failed for user '.$user->id.': '.$e->getMessage());
        }
    }
}
