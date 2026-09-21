<?php

namespace Modules\BackupCleanup\Providers;

use Modules\BackupCleanup\Console\ClearApplicationNotification;
use Modules\BackupCleanup\Console\SystemBackupAndCleanup;
use Modules\BackupCleanup\Models\Backup;
use Modules\BackupCleanup\Policies\BackupPolicy;
use Modules\BackupCleanup\View\Composers\BackupCleanupWidgetComposer;
use Mrj\Foundation\Support\ModuleServiceProvider;

class BackupCleanupServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'BackupCleanup';

    protected string $nameLower = 'backupcleanup';

    protected array $policies = [
        Backup::class => BackupPolicy::class,
    ];

    protected array $composers = [
        'backupcleanup::partials.dashboard-widget' => BackupCleanupWidgetComposer::class,
    ];

    protected array $commands = [
        SystemBackupAndCleanup::class,
        ClearApplicationNotification::class,
    ];
}
