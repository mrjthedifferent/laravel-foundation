<?php

namespace Modules\ImportDownloadManager\View\Composers;

use Illuminate\View\View;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Mrj\Foundation\Services\Dashboard\DashboardCache;

/**
 * Supplies the Import / Download dashboard widget. The queries live here rather than in the
 * Blade partial so they can be cached and so the view stays free of data access.
 *
 * These are unscoped, application-wide totals, so a single shared cache key is safe.
 * A widget that ever becomes user-scoped must gain a scope segment in its key.
 */
final readonly class ImportDownloadManagerWidgetComposer
{
    public function __construct(private DashboardCache $cache) {}

    public function compose(View $view): void
    {
        $view->with('widget', $this->data());
    }

    /**
     * @return array<string, mixed>|null null when the viewer may not see the widget
     */
    private function data(): ?array
    {
        if (! auth()->user()?->hasAnyPermission(['Download Import Manager Management'])) {
            return null;
        }

        return $this->cache->remember('widget:importdownloadmanager', fn (): array => [
            'total_jobs' => DownloadImportManager::query()->count(),
            'pending_jobs' => DownloadImportManager::query()->where('status', ImportStatus::Pending)->count(),
            'completed_jobs' => DownloadImportManager::query()->where('status', ImportStatus::Completed)->count(),
        ]);
    }
}
