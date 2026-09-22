<?php

namespace Modules\ErrorReport\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\SlackMessage;
use Modules\ErrorReport\Models\ErrorReport;
use Modules\ErrorReport\Notifications\ErrorReportNotification;
use Tests\TestCase;

class ErrorReportNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * laravel/slack-notification-channel was never a declared dependency, so
     * Illuminate\Notifications\Messages\SlackMessage did not exist at runtime
     * — the class import alone didn't fail, but any app that configured a
     * Slack error-report channel would fatal the moment an error was actually
     * reported, since toSlack() is only invoked at send time.
     */
    public function test_the_slack_message_builds_without_the_class_not_found_error(): void
    {
        $report = ErrorReport::factory()->create([
            'exception_class' => 'RuntimeException',
            'message' => 'Something broke',
            'file' => '/app/Http/Controllers/HomeController.php',
            'line' => 42,
            'occurrences' => 3,
        ]);

        $message = (new ErrorReportNotification($report))->toSlack((object) []);

        $this->assertInstanceOf(SlackMessage::class, $message);
        $this->assertSame('error', $message->level);
        $this->assertNotEmpty($message->attachments);
    }
}
