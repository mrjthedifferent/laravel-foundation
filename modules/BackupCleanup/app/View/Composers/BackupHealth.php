<?php

declare(strict_types=1);

namespace Modules\BackupCleanup\View\Composers;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Modules\BackupCleanup\Actions\ListBackupsAction;
use Mrj\Foundation\Support\HealthCheck;
use Override;
use Throwable;

/**
 * Whether a recent backup exists: none, or one older than two days, needs a look.
 */
final class BackupHealth extends HealthCheck
{
    /** Two days: a nightly backup that missed twice is a real gap. */
    private const int STALE_AFTER_HOURS = 48;

    public function __construct(private readonly ListBackupsAction $listBackups) {}

    #[Override]
    public function permissions(): array
    {
        return ['View Backup', 'Create Backup'];
    }

    #[Override]
    public function priority(): int
    {
        return 20;
    }

    #[Override]
    public function check(): array
    {
        try {
            $latest = $this->listBackups->execute()->first();
        } catch (Throwable) {
            // An unreachable or unconfigured backup disk is itself the finding.
            return [
                'status' => self::FAIL,
                'label' => __('backupcleanup::backupcleanup.health.label'),
                'detail' => __('backupcleanup::backupcleanup.health.unreachable'),
                'href' => route('admin.backups.index'),
            ];
        }

        $base = ['label' => __('backupcleanup::backupcleanup.health.label'), 'href' => route('admin.backups.index')];

        if ($latest === null) {
            return ['status' => self::WARN, 'detail' => __('backupcleanup::backupcleanup.health.none')] + $base;
        }

        $when = Carbon::createFromTimestamp($latest['date']);
        $detail = __('backupcleanup::backupcleanup.health.last', ['ago' => $when->diffForHumans(['syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW])]);

        return [
            'status' => $when->lt(now()->subHours(self::STALE_AFTER_HOURS)) ? self::WARN : self::OK,
            'detail' => $detail,
        ] + $base;
    }
}
