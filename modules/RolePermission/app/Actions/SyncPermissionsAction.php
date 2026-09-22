<?php

declare(strict_types=1);

namespace Modules\RolePermission\Actions;

use Illuminate\Support\Facades\Artisan;
use Modules\RolePermission\Database\Seeders\RolePermissionPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Re-seed the permissions every enabled module declares in its
 * config/permissions.php, and flush the cache. A Super Admin needs no
 * permissions (it passes every check), so no role is updated.
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
            '--class' => RolePermissionPermissionsSeeder::class,
            '--force' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
