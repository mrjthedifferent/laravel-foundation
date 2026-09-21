<?php

namespace Modules\Notification\Tests\Feature;

use App\Models\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Notifications\AppNotification;
use Modules\Settings\Models\Setting;
use Tests\TestCase;

class NotificationChannelGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array']);
    }

    /**
     * Toggles live in the settings store; the gate reads them there rather than from the
     * boot-time config snapshot, so a worker picks up a change without being restarted.
     */
    private function toggle(string $key, bool $enabled): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['group' => 'Notification Channels', 'type' => 'boolean', 'value' => $enabled ? '1' : '0', 'is_visible' => false],
        );
    }

    private function sentEmailCount(): int
    {
        return count(Mail::mailer('array')->getSymfonyTransport()->messages());
    }

    private function notifyPasswordReset(User $user): void
    {
        $user->notify(new AppNotification(
            title: 'Password Reset',
            body: 'body',
            type: NotificationType::Info,
            data: ['type' => 'password_reset'],
            channels: ['database', 'mail'],
        ));
    }

    public function test_mail_is_suppressed_when_toggle_off_but_in_app_still_recorded(): void
    {
        $this->toggle('mail_notify_password_reset', false);
        $user = User::factory()->create();

        $this->notifyPasswordReset($user);

        $this->assertSame(0, $this->sentEmailCount(), 'No email should be sent when the toggle is off');
        $this->assertSame(1, DB::table('notifications')->count(), 'In-app (database) notification must still be recorded');
    }

    public function test_mail_is_sent_when_toggle_on(): void
    {
        $this->toggle('mail_notify_password_reset', true);
        $user = User::factory()->create();

        $this->notifyPasswordReset($user);

        $this->assertSame(1, $this->sentEmailCount(), 'Email should be sent when the toggle is on');
        $this->assertSame(1, DB::table('notifications')->count());
    }

    public function test_in_app_is_suppressed_when_inapp_toggle_off_but_mail_still_sent(): void
    {
        $this->toggle('inapp_notify_password_reset', false);
        $user = User::factory()->create();

        $this->notifyPasswordReset($user);

        $this->assertSame(1, $this->sentEmailCount(), 'Email must be unaffected by the in-app toggle');
        $this->assertSame(0, DB::table('notifications')->count(), 'No in-app notification should be recorded when the in-app toggle is off');
    }
}
