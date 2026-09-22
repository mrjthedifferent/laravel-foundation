<?php

namespace Modules\RolePermission\Database\Seeders;

use Illuminate\Database\Seeder;
use Mrj\Foundation\Support\Roles;
use Spatie\Permission\Models\Role;

class RolePermissionDatabaseSeeder extends Seeder
{
    /**
     * The roles every project starts with, holding no permissions: a project seeds
     * what Admin and User may do in its own seeder. Super Admin is not a role but a
     * flag on the user (php artisan foundation:super-admin).
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => Roles::admin()]);
        Role::firstOrCreate(['name' => Roles::user()]);
    }
}
