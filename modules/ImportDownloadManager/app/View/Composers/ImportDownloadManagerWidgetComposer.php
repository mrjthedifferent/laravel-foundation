<?php

declare(strict_types=1);

namespace Modules\ImportDownloadManager\View\Composers;

use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Mrj\Foundation\Support\WidgetComposer;
use Override;

/**
 * Supplies the Import / Download dashboard widget.
 *
 * This is an unscoped, application-wide total, so a single shared cache key
 * is safe. A widget that ever becomes user-scoped must gain a scope segment
 * in its key.
 */
final class ImportDownloadManagerWidgetComposer extends WidgetComposer
{
    #[Override]
    protected function permissions(): array
    {
        return ['Download Import Manager Management'];
    }

    #[Override]
    protected function key(): string
    {
        return 'importdownloadmanager';
    }

    #[Override]
    protected function build(): array
    {
        return [
            'total_jobs' => DownloadImportManager::query()->count(),
            'pending_jobs' => DownloadImportManager::query()->where('status', ImportStatus::Pending)->count(),
            'completed_jobs' => DownloadImportManager::query()->where('status', ImportStatus::Completed)->count(),
        ];
    }
}
