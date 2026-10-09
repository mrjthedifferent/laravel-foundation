<?php

declare(strict_types=1);

namespace Modules\BackupCleanup\View\Composers;

use Mrj\Foundation\Support\QuickActionComposer;
use Override;

/**
 * A shortcut to the backups page, where one is started.
 */
final class BackupQuickActions extends QuickActionComposer
{
    #[Override]
    public function priority(): int
    {
        return 40;
    }

    #[Override]
    public function actions(): array
    {
        return [[
            'label' => __('backupcleanup::backupcleanup.quick.backups'),
            'icon' => 'ph ph-database',
            'href' => route('admin.backups.index'),
            'permission' => 'Create Backup',
        ]];
    }
}
