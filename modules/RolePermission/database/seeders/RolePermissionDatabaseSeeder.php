<?php

namespace Modules\RolePermission\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionDatabaseSeeder extends Seeder
{
    /**
     * The roles every project starts with. Super Admin holds every permission;
     * a project seeds its own roles, and what Admin and User may do, in its own seeder.
     */
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'User']);

        $superAdmin->syncPermissions(Permission::pluck('name'));
    }
}
