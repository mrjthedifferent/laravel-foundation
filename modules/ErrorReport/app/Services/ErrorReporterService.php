<?php

namespace Modules\ErrorReport\Services;

use Illuminate\Support\Facades\Request;
use Modules\ErrorReport\Jobs\NotifyErrorJob;
use Modules\ErrorReport\Models\ErrorReport;
use Mrj\Foundation\Contracts\ErrorReporter;
use Override;
use Throwable;

final readonly class ErrorReporterService implements ErrorReporter
{
    #[Override]
    public function capture(Throwable $e): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldNotReport($e)) {
            return;
        }

        $fingerprint = $this->buildFingerprint($e);
        $now = now();

        $report = ErrorReport::firstOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'uuid' => str()->uuid()->toString(),
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $this->formatTrace($e),
                'request_method' => Request::method(),
                'request_path' => Request::path(),
                'request_url' => $this->redactUrl(Request::fullUrl()),
                'user_id' => auth()->id(),
                'context' => [],
                'occurrences' => 1,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]
        );

        if (! $report->wasRecentlyCreated) {
            $report->increment('occurrences');
            $report->update([
                'last_seen_at' => $now,
                'message' => $e->getMessage(),
                'trace' => $this->formatTrace($e),
                'request_method' => Request::method(),
                'request_path' => Request::path(),
                'request_url' => $this->redactUrl(Request::fullUrl()),
                'user_id' => auth()->id(),
            ]);
        }

        NotifyErrorJob::dispatch($report);
    }

    private function isEnabled(): bool
    {
        return filter_var(config('settings.error_report_enabled.value', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function shouldNotReport(Throwable $e): bool
    {
        $dontReport = config('settings.error_report_dont_report.value', []);

        if (empty($dontReport)) {
            return false;
        }

        $classes = is_string($dontReport) ? json_decode($dontReport, true) : $dontReport;

        if (! is_array($classes)) {
            return false;
        }

        foreach ($classes as $class) {
            if ($e instanceof $class) {
                return true;
            }
        }

        return false;
    }

    private function buildFingerprint(Throwable $e): string
    {
        return hash('sha256', get_class($e).$e->getFile().$e->getLine());
    }

    /**
     * Replaces the values of sensitive query-string parameters with [REDACTED]
     * so captured URLs never persist tokens, passwords, or OTPs.
     */
    private function redactUrl(string $url): string
    {
        $parts = parse_url($url);

        if (empty($parts['query'])) {
            return $url;
        }

        parse_str($parts['query'], $params);

        $sensitive = ['token', 'api_token', 'access_token', 'password', 'password_confirmation', 'secret', 'api_key', 'otp', 'code', 'signature'];

        foreach ($params as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $params[$key] = '[REDACTED]';
            }
        }

        $base = ($parts['scheme'] ?? 'http').'://'.($parts['host'] ?? '')
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .($parts['path'] ?? '');

        return $base.'?'.http_build_query($params);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatTrace(Throwable $e): array
    {
        $trace = [];

        foreach ($e->getTrace() as $frame) {
            $trace[] = [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
            ];
        }

        return $trace;
    }
}
