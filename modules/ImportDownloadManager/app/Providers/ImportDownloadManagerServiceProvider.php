<?php

declare(strict_types=1);

namespace Modules\ImportDownloadManager\Providers;

use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Modules\ImportDownloadManager\Policies\DownloadImportManagerPolicy;
use Modules\ImportDownloadManager\Support\EloquentImportTracker;
use Modules\ImportDownloadManager\View\Composers\ImportDownloadManagerWidgetComposer;
use Mrj\Foundation\Contracts\ImportTracker;
use Mrj\Foundation\Support\ModuleServiceProvider;
use Override;

class ImportDownloadManagerServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ImportDownloadManager';

    protected string $nameLower = 'importdownloadmanager';

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->singleton(ImportTracker::class, EloquentImportTracker::class);
    }

    protected array $morphMap = [
        'download_import_manager' => DownloadImportManager::class,
    ];

    protected array $policies = [
        DownloadImportManager::class => DownloadImportManagerPolicy::class,
    ];

    protected array $composers = [
        'importdownloadmanager::partials.dashboard-widget' => ImportDownloadManagerWidgetComposer::class,
    ];
}
