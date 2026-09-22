<?php

declare(strict_types=1);

namespace Modules\BackupCleanup\View\Composers;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Modules\BackupCleanup\Actions\ListBackupsAction;
use Mrj\Foundation\Services\Dashboard\DashboardCache;
use Mrj\Foundation\Support\StatComposer;
use Override;
use Throwable;

/**
 * How fresh the newest backup is, as a dashboard headline stat.
 *
 * Listing backups stats every file on the disk, which is a round-trip per file
 * once that disk is remote — so this is one of the payloads most worth caching.
 */
final class BackupStatComposer extends StatComposer
{
    public function __construct(
        DashboardCache $cache,
        private readonly ListBackupsAction $listBackups,
    ) {
        parent::__construct($cache);
    }

    #[Override]
    public function priority(): int
    {
        return 40;
    }

    #[Override]
    protected function permissions(): array
    {
        return ['View Backup', 'Create Backup'];
    }

    #[Override]
    protected function key(): string
    {
        return 'backup';
    }

    #[Override]
    protected function build(): array
    {
        try {
            $latest = $this->listBackups->execute()->first();
        } catch (Throwable) {
            // An unreachable or unconfigured backup disk must not break the dashboard.
            $latest = null;
        }

        return [[
            'label' => __('backupcleanup::backupcleanup.stat.last_backup'),
            'value' => $latest === null
                ? __('backupcleanup::backupcleanup.stat.never')
                : Carbon::createFromTimestamp($latest['date'])
                    ->diffForHumans(['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'short' => true]),
            'icon' => 'ph-database',
            'color' => $latest === null ? 'warning' : 'success',
            'href' => route('admin.backups.index'),
            'caption' => $latest === null
                ? __('backupcleanup::backupcleanup.stat.none_yet')
                : $this->size((int) $latest['size']),
        ]];
    }

    /**
     * Only this card shows a file size, so the formatting stays here rather than
     * becoming a global helper.
     */
    private function size(int $bytes): string
    {
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1).' MB'
            : number_format(max($bytes, 0) / 1024, 1).' KB';
    }
}
