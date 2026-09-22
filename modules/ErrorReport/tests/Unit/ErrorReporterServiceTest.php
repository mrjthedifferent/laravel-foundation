<?php

namespace Modules\ErrorReport\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Request as RequestFacade;
use InvalidArgumentException;
use LogicException;
use Modules\ErrorReport\Jobs\NotifyErrorJob;
use Modules\ErrorReport\Models\ErrorReport;
use Modules\ErrorReport\Services\ErrorReporterService;
use Mrj\Foundation\Contracts\ErrorReporter;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class ErrorReporterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake([NotifyErrorJob::class]);
        config([
            'settings.error_report_enabled.value' => true,
            'settings.error_report_dont_report.value' => [],
        ]);
    }

    /**
     * The same throw site on every call, so repeated captures share a fingerprint.
     */
    private function exception(string $message = 'Boom'): RuntimeException
    {
        return new RuntimeException($message);
    }

    private function capture(Throwable $e): void
    {
        app(ErrorReporter::class)->capture($e);
    }

    public function test_the_module_binds_the_real_reporter(): void
    {
        $this->assertInstanceOf(ErrorReporterService::class, app(ErrorReporter::class));
    }

    public function test_nothing_is_recorded_while_reporting_is_disabled(): void
    {
        config(['settings.error_report_enabled.value' => false]);

        $this->capture($this->exception());

        $this->assertSame(0, ErrorReport::count());
        Bus::assertNotDispatched(NotifyErrorJob::class);
    }

    public function test_a_new_exception_is_recorded_and_a_notification_queued(): void
    {
        $this->capture($this->exception('First failure'));

        $this->assertDatabaseHas('error_reports', [
            'exception_class' => RuntimeException::class,
            'message' => 'First failure',
            'occurrences' => 1,
            'file' => __FILE__,
        ]);
        $report = ErrorReport::query()->sole();
        $this->assertNotEmpty($report->getAttribute('trace'));
        Bus::assertDispatched(NotifyErrorJob::class, fn (NotifyErrorJob $job): bool => $job->errorReport->is($report));
    }

    public function test_repeats_of_the_same_exception_are_grouped(): void
    {
        foreach (['one', 'two', 'three'] as $message) {
            $this->capture($this->exception($message));
        }

        $this->assertSame(1, ErrorReport::count());
        $this->assertDatabaseHas('error_reports', ['occurrences' => 3, 'message' => 'three']);
        Bus::assertDispatchedTimes(NotifyErrorJob::class, 3);
    }

    public function test_different_throw_sites_are_recorded_separately(): void
    {
        $this->capture($this->exception());
        $this->capture(new RuntimeException('Elsewhere'));

        $this->assertSame(2, ErrorReport::count());
    }

    public function test_classes_in_the_dont_report_list_are_ignored_including_subclasses(): void
    {
        config(['settings.error_report_dont_report.value' => json_encode([LogicException::class])]);

        $this->capture(new InvalidArgumentException('ignored subclass'));
        $this->assertSame(0, ErrorReport::count());

        $this->capture($this->exception());
        $this->assertSame(1, ErrorReport::count());
    }

    public function test_sensitive_query_parameters_are_redacted_from_the_url(): void
    {
        $this->app->instance('request', Request::create('https://app.test/reset?token=abc123&page=2&OTP=987654'));
        RequestFacade::clearResolvedInstance('request');

        $this->capture($this->exception());

        $url = (string) ErrorReport::query()->sole()->getAttribute('request_url');
        $this->assertStringNotContainsString('abc123', $url);
        $this->assertStringNotContainsString('987654', $url);
        $this->assertStringContainsString('page=2', $url);
        $this->assertStringContainsString('token='.urlencode('[REDACTED]'), $url);
    }
}
