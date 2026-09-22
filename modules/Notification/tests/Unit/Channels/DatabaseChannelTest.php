<?php

namespace Modules\Notification\Tests\Unit\Channels;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification as BaseNotification;
use Modules\Notification\Channels\DatabaseChannel;
use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Models\Notification;
use Tests\TestCase;

/**
 * The channel used to require toDatabase() and return early otherwise, silently dropping every
 * notification that only defines toArray() — eight registered types, all of which declared the
 * database channel and exposed an in-app toggle that had nothing to gate.
 */
class DatabaseChannelTest extends TestCase
{
    use RefreshDatabase;

    private function channel(): DatabaseChannel
    {
        return new DatabaseChannel;
    }

    public function test_persists_a_notification_that_only_defines_to_array(): void
    {
        $user = User::factory()->create();

        $notification = new class extends BaseNotification
        {
            public function toArray(object $notifiable): array
            {
                return ['type' => 'missing_attendance', 'date' => '2026-09-03'];
            }
        };

        $this->channel()->send($user, $notification);

        $row = Notification::query()->sole();
        $this->assertSame($user->id, (int) $row->notifiable_id);
        $this->assertSame('missing_attendance', $row->data['type']);
        $this->assertSame('2026-09-03', $row->data['date']);
    }

    public function test_to_database_still_wins_when_both_are_defined(): void
    {
        $user = User::factory()->create();

        $notification = new class extends BaseNotification
        {
            public function toDatabase(object $notifiable): array
            {
                return ['from' => 'toDatabase'];
            }

            public function toArray(object $notifiable): array
            {
                return ['from' => 'toArray'];
            }
        };

        $this->channel()->send($user, $notification);

        $this->assertSame('toDatabase', Notification::query()->sole()->data['from']);
    }

    public function test_a_notification_with_neither_method_is_skipped(): void
    {
        $user = User::factory()->create();

        $this->channel()->send($user, new class extends BaseNotification {});

        $this->assertSame(0, Notification::query()->count());
    }

    public function test_the_notification_type_is_carried_through(): void
    {
        $user = User::factory()->create();

        $notification = new class extends BaseNotification
        {
            public NotificationType $type = NotificationType::Error;

            public function toArray(object $notifiable): array
            {
                return ['type' => 'something_failed'];
            }
        };

        $this->channel()->send($user, $notification);

        $this->assertSame(NotificationType::Error->value, Notification::query()->sole()->type);
    }
}
