<?php

declare(strict_types=1);

namespace Modules\ImportDownloadManager\Providers;

use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Modules\ImportDownloadManager\Policies\DownloadImportManagerPolicy;
use Modules\ImportDownloadManager\View\Composers\ImportDownloadManagerWidgetComposer;
use Mrj\Foundation\Support\ModuleServiceProvider;

class ImportDownloadManagerServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ImportDownloadManager';

    protected string $nameLower = 'importdownloadmanager';

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
