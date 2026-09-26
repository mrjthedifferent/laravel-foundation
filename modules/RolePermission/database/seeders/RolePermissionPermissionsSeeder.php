<?php

namespace Modules\RolePermission\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Mrj\Foundation\Support\Tenancy;
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
     *
     * With foundation.tenancy enabled, only the modules that belong where the
     * seeder runs (the central database or a tenant's) contribute, and an entry
     * carrying 'contexts' => ['central'] (or ['tenant']) is seeded only there.
     * What a tenant's database holds is exactly what its admins can grant.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Module::allEnabled() as $module) {
            if (! Tenancy::moduleBelongsHere($module)) {
                continue;
            }

            $permissionsConfig = $module->getPath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'permissions.php';

            if (! File::exists($permissionsConfig)) {
                continue;
            }

            $permissions = require $permissionsConfig;

            foreach ($permissions as $permission) {
                if (! Tenancy::allows($permission['contexts'] ?? null)) {
                    continue;
                }

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
