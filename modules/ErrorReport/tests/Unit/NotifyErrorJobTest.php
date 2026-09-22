<?php

namespace Modules\ErrorReport\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\ErrorReport\Jobs\NotifyErrorJob;
use Modules\ErrorReport\Models\ErrorReport;
use Modules\ErrorReport\Notifications\ErrorReportNotification;
use Tests\TestCase;

class NotifyErrorJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'settings.error_report_channels.value' => ['mail'],
            'settings.error_report_email_recipients.value' => 'ops@example.com, oncall@example.com',
            'settings.error_report_throttle_minutes.value' => 60,
        ]);
    }

    public function test_it_mails_every_configured_recipient_and_stamps_last_notified_at(): void
    {
        $report = ErrorReport::factory()->create();

        (new NotifyErrorJob($report))->handle();

        Notification::assertSentTo(
            new AnonymousNotifiable,
            ErrorReportNotification::class,
            fn ($notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === ['ops@example.com', 'oncall@example.com'],
        );
        $this->assertDatabaseMissing('error_reports', ['id' => $report->id, 'last_notified_at' => null]);
    }

    public function test_it_falls_back_to_the_development_support_email(): void
    {
        config([
            'settings.error_report_email_recipients.value' => null,
            'settings.development_support_email.value' => 'dev@example.com',
        ]);

        (new NotifyErrorJob(ErrorReport::factory()->create()))->handle();

        Notification::assertSentTo(
            new AnonymousNotifiable,
            ErrorReportNotification::class,
            fn ($notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === ['dev@example.com'],
        );
    }

    public function test_a_recently_notified_report_is_throttled(): void
    {
        $report = ErrorReport::factory()->create(['last_notified_at' => now()->subMinutes(10)]);
        $stamped = DB::table('error_reports')->where('id', $report->id)->value('last_notified_at');

        (new NotifyErrorJob($report))->handle();

        Notification::assertNothingSent();
        $this->assertSame($stamped, DB::table('error_reports')->where('id', $report->id)->value('last_notified_at'));
    }

    public function test_the_throttle_window_expires(): void
    {
        $report = ErrorReport::factory()->create(['last_notified_at' => now()->subMinutes(61)]);

        (new NotifyErrorJob($report))->handle();

        Notification::assertSentTimes(ErrorReportNotification::class, 1);
    }

    public function test_a_zero_throttle_always_notifies(): void
    {
        config(['settings.error_report_throttle_minutes.value' => 0]);
        $report = ErrorReport::factory()->create(['last_notified_at' => now()]);

        (new NotifyErrorJob($report))->handle();

        Notification::assertSentTimes(ErrorReportNotification::class, 1);
    }

    public function test_unconfigured_channels_send_nothing(): void
    {
        config([
            'settings.error_report_channels.value' => json_encode(['slack', 'telegram']),
            'settings.error_report_slack_webhook.value' => null,
            'logging.channels.slack.url' => null,
            'settings.error_report_telegram_chat_id.value' => null,
            'settings.error_report_telegram_bot_token.value' => null,
        ]);

        (new NotifyErrorJob(ErrorReport::factory()->create()))->handle();

        Notification::assertNothingSent();
    }
}
