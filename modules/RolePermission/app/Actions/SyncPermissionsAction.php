<?php

namespace Modules\RolePermission\Actions;

use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

/**
 * Re-seed all permissions from PermissionSeeder and flush the cache.
 *
 * Usage:
 *   app(SyncPermissionsAction::class)->execute();
 */
final readonly class SyncPermissionsAction
{
    public function execute(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\PermissionSeeder',
            '--force' => true,
        ]);
    }
}
