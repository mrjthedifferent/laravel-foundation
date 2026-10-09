<?php

namespace Mrj\Foundation\Services\Dashboard\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mrj\Foundation\Support\HealthCheck;
use Override;

/**
 * Failed queue jobs: none is fine, any is a warning, a pile is a failure. Where the
 * failed-jobs table does not exist (a project using another failed-job driver) the
 * check reports nothing it can know, so it passes.
 *
 * @internal
 */
final class QueueHealth extends HealthCheck
{
    private const int PILE = 25;

    #[Override]
    public function permissions(): array
    {
        return ['View Logs', 'Developer Setting'];
    }

    #[Override]
    public function priority(): int
    {
        return 30;
    }

    #[Override]
    public function check(): array
    {
        $table = (string) config('queue.failed.table', 'failed_jobs');
        $failed = Schema::hasTable($table) ? DB::table($table)->count() : 0;

        return [
            'status' => match (true) {
                $failed === 0 => self::OK,
                $failed < self::PILE => self::WARN,
                default => self::FAIL,
            },
            'label' => __('foundation::foundation.dashboard.health_queue'),
            'detail' => $failed === 0
                ? __('foundation::foundation.dashboard.health_queue_ok')
                : trans_choice('foundation::foundation.dashboard.health_queue_failed', $failed, ['count' => number_format($failed)]),
        ];
    }
}
