<?php

namespace Modules\RolePermission\Actions;

use Illuminate\Support\Facades\Artisan;
use Modules\RolePermission\Database\Seeders\RolePermissionPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Re-seed the permissions every enabled module declares in its
 * config/permissions.php, and flush the cache. Super Admin holds every
 * permission, so it is given the new ones too.
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

        Role::where('name', 'Super Admin')->first()?->givePermissionTo(Permission::all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
