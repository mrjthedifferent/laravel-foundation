<?php

namespace Modules\Notification\Tests\Unit\Actions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Notification\Actions\NotifyAction;
use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Jobs\BroadcastNotificationJob;
use Modules\Notification\Notifications\AppNotification;
use Tests\TestCase;

class NotificationActionsTest extends TestCase
{
    use RefreshDatabase;

    // ── toUser ────────────────────────────────────────────────────────────────

    public function test_to_user_uses_system_default_channels_when_none_given(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        NotifyAction::toUser($user, 'Hello', 'World');

        Notification::assertSentTo($user, AppNotification::class, function (AppNotification $n) {
            return $n->title === 'Hello'
                && $n->body === 'World'
                && $n->type === NotificationType::Info
                && $n->channels === null; // falls back to config('notification.channels')
        });
    }

    public function test_to_user_respects_single_explicit_channel(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        NotifyAction::toUser($user, 'T', 'B', channels: ['database']);

        Notification::assertSentTo($user, AppNotification::class, function (AppNotification $n) {
            return $n->channels === ['database'];
        });
    }

    public function test_to_user_supports_any_channel_combination(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        NotifyAction::toUser($user, 'T', 'B', channels: ['database', 'fcm', 'mail', 'sms']);

        Notification::assertSentTo($user, AppNotification::class, function (AppNotification $n) {
            return $n->channels === ['database', 'fcm', 'mail', 'sms'];
        });
    }

    public function test_to_user_forwards_type_and_data(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        NotifyAction::toUser(
            notifiable: $user,
            title: 'Export done',
            body: 'Your file is ready.',
            type: NotificationType::Export,
            data: ['url' => 'https://example.com/file.csv'],
        );

        Notification::assertSentTo($user, AppNotification::class, function (AppNotification $n) {
            return $n->type === NotificationType::Export
                && $n->data === ['url' => 'https://example.com/file.csv'];
        });
    }

    public function test_to_user_works_with_any_notifiable_model(): void
    {
        Notification::fake();

        // Any model using the Notifiable trait works — not just User
        $notifiable = User::factory()->create();

        NotifyAction::toUser($notifiable, 'T', 'B', channels: ['mail']);

        Notification::assertSentTo($notifiable, AppNotification::class, function (AppNotification $n) {
            return $n->channels === ['mail'];
        });
    }

    public function test_to_user_passes_mail_template_through_to_app_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $mailable = new class extends Mailable
        {
            public function build(): static
            {
                return $this->view('welcome');
            }
        };

        NotifyAction::toUser($user, 'T', 'B', channels: ['mail'], mailTemplate: $mailable);

        Notification::assertSentTo($user, AppNotification::class, function (AppNotification $n) use ($mailable) {
            return $n->mailTemplate === $mailable;
        });
    }

    public function test_to_mail_returns_mailable_when_template_provided(): void
    {
        $mailable = new class extends Mailable
        {
            public function build(): static
            {
                return $this->view('welcome');
            }
        };

        $notification = new AppNotification(
            title: 'T',
            body: 'B',
            mailTemplate: $mailable,
        );

        $result = $notification->toMail(new \stdClass);

        $this->assertSame($mailable, $result);
    }

    public function test_to_mail_falls_back_to_mail_message_when_no_template(): void
    {
        $notification = new AppNotification(title: 'Hello', body: 'World');

        $result = $notification->toMail(new \stdClass);

        $this->assertInstanceOf(MailMessage::class, $result);
    }

    // ── push ──────────────────────────────────────────────────────────────────

    public function test_push_dispatches_job_with_user_id_and_default_fcm_channel(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        NotifyAction::push($user, 'Alert', 'Check your account.');

        Queue::assertPushed(BroadcastNotificationJob::class, function (BroadcastNotificationJob $job) use ($user) {
            return $job->title === 'Alert'
                && $job->body === 'Check your account.'
                && $job->userId === $user->id
                && $job->channels === ['fcm'];
        });
    }

    public function test_push_forwards_custom_channels(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        NotifyAction::push($user, 'T', 'B', channels: ['fcm', 'sms']);

        Queue::assertPushed(BroadcastNotificationJob::class, function (BroadcastNotificationJob $job) {
            return $job->channels === ['fcm', 'sms'];
        });
    }

    // ── broadcast ─────────────────────────────────────────────────────────────

    public function test_broadcast_dispatches_job_without_user_id_and_default_fcm_channel(): void
    {
        Queue::fake();

        NotifyAction::broadcast('Maintenance', 'Server restarts at midnight.');

        Queue::assertPushed(BroadcastNotificationJob::class, function (BroadcastNotificationJob $job) {
            return $job->title === 'Maintenance'
                && $job->body === 'Server restarts at midnight.'
                && $job->userId === null
                && $job->channels === ['fcm'];
        });
    }

    public function test_broadcast_forwards_custom_channels_and_data(): void
    {
        Queue::fake();

        NotifyAction::broadcast('T', 'B', data: ['key' => 'value'], channels: ['fcm', 'sms']);

        Queue::assertPushed(BroadcastNotificationJob::class, function (BroadcastNotificationJob $job) {
            return $job->data === ['key' => 'value']
                && $job->channels === ['fcm', 'sms'];
        });
    }
}
