<?php

namespace Modules\RolePermission\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Iterates over all enabled modules and seeds any permissions defined
     * in their config/permissions.php file into the permissions table.
     * Modules with no permissions simply return an empty array.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Module::allEnabled() as $module) {
            $permissionsConfig = $module->getPath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'permissions.php';

            if (! File::exists($permissionsConfig)) {
                continue;
            }

            $permissions = require $permissionsConfig;

            foreach ($permissions as $permission) {
                Permission::updateOrCreate(
                    ['name' => $permission['name']],
                    [
                        'module_name' => $permission['module_name'],
                        'guard_name' => config('foundation.guards.web'),
                    ]
                );
            }
        }
    }
}
