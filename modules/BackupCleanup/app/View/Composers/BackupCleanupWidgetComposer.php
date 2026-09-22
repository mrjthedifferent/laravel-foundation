<?php

namespace Modules\BackupCleanup\View\Composers;

use Modules\BackupCleanup\Actions\ListBackupsAction;
use Mrj\Foundation\Services\Dashboard\DashboardCache;
use Mrj\Foundation\Support\WidgetComposer;
use Override;
use Throwable;

/**
 * Supplies the Backup dashboard widget. This one is worth caching more than most:
 * listing backups stats every file on the disk, which is a round-trip per file once
 * the backup disk is remote.
 */
final class BackupCleanupWidgetComposer extends WidgetComposer
{
    public function __construct(
        DashboardCache $cache,
        private readonly ListBackupsAction $listBackups,
    ) {
        parent::__construct($cache);
    }

    #[Override]
    protected function permissions(): array
    {
        return ['View Backup', 'Create Backup'];
    }

    #[Override]
    protected function key(): string
    {
        return 'backupcleanup';
    }

    #[Override]
    protected function build(): array
    {
        try {
            $backups = $this->listBackups->execute();

            return [
                'count' => $backups->count(),
                'latest' => $backups->first(),
            ];
        } catch (Throwable) {
            // An unreachable or unconfigured backup disk must not break the dashboard.
            return ['count' => 0, 'latest' => null];
        }
    }
}
