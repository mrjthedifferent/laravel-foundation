<?php

namespace Modules\Notification\Tests\Unit\Listeners;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Notification as BaseNotification;
use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Listeners\GateNotificationChannels;
use Modules\Notification\Notifications\AppNotification;
use Modules\Otp\Notifications\SendVerificationCode;
use Modules\Settings\Models\Setting;
use stdClass;
use Tests\TestCase;

class GateNotificationChannelsTest extends TestCase
{
    use RefreshDatabase;

    private GateNotificationChannels $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new GateNotificationChannels;
    }

    /**
     * Toggles are read from the settings store, not from the boot-time config snapshot, so these
     * tests write real rows - the same path production takes.
     */
    private function toggle(string $key, bool $enabled): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['group' => 'Notification Channels', 'type' => 'boolean', 'value' => $enabled ? '1' : '0', 'is_visible' => false],
        );
    }

    private function event(object $notification, string $channel): NotificationSending
    {
        return new NotificationSending(notifiable: new stdClass, notification: $notification, channel: $channel);
    }

    private function passwordReset(): AppNotification
    {
        return new AppNotification('Password Reset', 'body', NotificationType::Info, ['type' => 'password_reset']);
    }

    public function test_cancels_mail_when_class_based_toggle_is_off(): void
    {
        $this->toggle('mail_notify_otp_verification', false);

        $this->assertFalse(
            $this->listener->handle($this->event(new SendVerificationCode, 'mail'))
        );
    }

    public function test_allows_mail_when_toggle_is_on(): void
    {
        $this->toggle('mail_notify_otp_verification', true);

        $this->assertTrue(
            $this->listener->handle($this->event(new SendVerificationCode, 'mail'))
        );
    }

    public function test_mail_toggle_does_not_affect_other_channels(): void
    {
        $this->toggle('mail_notify_password_reset', false);

        $this->assertTrue($this->listener->handle($this->event($this->passwordReset(), 'database')));
        $this->assertTrue($this->listener->handle($this->event($this->passwordReset(), 'fcm')));
    }

    public function test_inapp_toggle_off_cancels_database_and_broadcast(): void
    {
        $this->toggle('inapp_notify_password_reset', false);

        $this->assertFalse($this->listener->handle($this->event($this->passwordReset(), 'database')));
        $this->assertFalse($this->listener->handle($this->event($this->passwordReset(), 'broadcast')));
        $this->assertTrue($this->listener->handle($this->event($this->passwordReset(), 'mail')));
    }

    public function test_push_toggle_off_cancels_fcm_only(): void
    {
        $this->toggle('push_notify_password_reset', false);

        $this->assertFalse($this->listener->handle($this->event($this->passwordReset(), 'fcm')));
        $this->assertTrue($this->listener->handle($this->event($this->passwordReset(), 'database')));
    }

    public function test_sms_toggle_off_cancels_sms(): void
    {
        $this->toggle('sms_notify_password_reset', false);

        $this->assertFalse($this->listener->handle($this->event($this->passwordReset(), 'sms')));
    }

    public function test_unsupported_channel_is_never_gated(): void
    {
        // SendVerificationCode only supports mail + sms; the database channel
        // is outside its capability list so the toggle key is ignored.
        $this->toggle('inapp_notify_otp_verification', false);

        $this->assertTrue(
            $this->listener->handle($this->event(new SendVerificationCode, 'database'))
        );
    }

    public function test_locked_channel_always_sends(): void
    {
        // OTP over SMS is locked on — disabling it would break phone login.
        $this->toggle('sms_notify_otp_verification', false);

        $this->assertTrue(
            $this->listener->handle($this->event(new SendVerificationCode, 'sms'))
        );
    }

    public function test_unknown_channel_is_never_gated(): void
    {
        $this->toggle('mail_notify_password_reset', false);

        $this->assertTrue($this->listener->handle($this->event($this->passwordReset(), 'slack')));
    }

    public function test_matches_app_notification_by_data_type(): void
    {
        $this->toggle('mail_notify_password_reset', false);

        $this->assertFalse($this->listener->handle($this->event($this->passwordReset(), 'mail')));

        // A different AppNotification data type is unaffected by the password_reset toggle.
        $other = new AppNotification('Other', 'body', NotificationType::Info, ['type' => 'roles_changed']);
        $this->toggle('mail_notify_roles_changed', true);
        $this->assertTrue($this->listener->handle($this->event($other, 'mail')));
    }

    public function test_a_toggle_switched_off_after_boot_is_honoured(): void
    {
        // The production failure: settings are copied into config() once at boot, but queued
        // notifications are sent by long-lived workers. A worker that started while the toggle
        // was ON kept sending after it was switched OFF, because its config never refreshed.
        $this->toggle('push_notify_password_reset', true);
        $this->assertTrue($this->listener->handle($this->event($this->passwordReset(), 'fcm')));

        // Simulate the boot-time snapshot going stale: config still says "on"...
        config(['settings.push_notify_password_reset.value' => true]);

        // ...while the setting itself has since been switched off.
        $this->toggle('push_notify_password_reset', false);

        $this->assertFalse(
            $this->listener->handle($this->event($this->passwordReset(), 'fcm')),
            'the gate must read the current setting, not the value captured at boot',
        );
    }

    public function test_unregistered_notification_always_sends(): void
    {
        $unregistered = new class extends BaseNotification {};

        $this->assertTrue($this->listener->handle($this->event($unregistered, 'mail')));
        $this->assertTrue($this->listener->handle($this->event($unregistered, 'database')));
    }

    public function test_missing_setting_defaults_to_sending(): void
    {
        // No rows at all - every gate defaults to sending.

        $this->assertTrue($this->listener->handle($this->event(new SendVerificationCode, 'mail')));
        $this->assertTrue($this->listener->handle($this->event($this->passwordReset(), 'database')));
        $this->assertTrue($this->listener->handle($this->event($this->passwordReset(), 'fcm')));
        $this->assertTrue($this->listener->handle($this->event($this->passwordReset(), 'sms')));
    }
}
