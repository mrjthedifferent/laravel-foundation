<?php

namespace Modules\BackupCleanup\View\Composers;

use Illuminate\View\View;
use Modules\BackupCleanup\Actions\ListBackupsAction;
use Mrj\Foundation\Services\Dashboard\DashboardCache;
use Throwable;

/**
 * Supplies the Backup dashboard widget. This one is worth caching more than most:
 * listing backups stats every file on the disk, which is a round-trip per file once
 * the backup disk is remote.
 */
final readonly class BackupCleanupWidgetComposer
{
    public function __construct(
        private DashboardCache $cache,
        private ListBackupsAction $listBackups,
    ) {}

    public function compose(View $view): void
    {
        $view->with('widget', $this->data());
    }

    /**
     * @return array<string, mixed>|null null when the viewer may not see the widget
     */
    private function data(): ?array
    {
        if (! auth()->user()?->hasAnyPermission(['View Backup', 'Create Backup'])) {
            return null;
        }

        return $this->cache->remember('widget:backupcleanup', function (): array {
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
        });
    }
}
