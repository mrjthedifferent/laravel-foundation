<?php

namespace Modules\RolePermission\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\RolePermission\Database\Seeders\RolePermissionDatabaseSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_default_role_names(): void
    {
        (new RolePermissionDatabaseSeeder)->run();

        $this->assertDatabaseHas('roles', ['name' => 'Super Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'User']);
    }

    public function test_it_seeds_a_projects_configured_role_names(): void
    {
        config(['foundation.roles' => [
            'super_admin' => 'Owner',
            'admin' => 'Staff',
            'user' => 'Member',
        ]]);

        (new RolePermissionDatabaseSeeder)->run();

        $this->assertDatabaseHas('roles', ['name' => 'Owner']);
        $this->assertDatabaseHas('roles', ['name' => 'Staff']);
        $this->assertDatabaseHas('roles', ['name' => 'Member']);
        $this->assertDatabaseMissing('roles', ['name' => 'Super Admin']);
    }

    public function test_the_configured_super_admin_role_receives_every_permission(): void
    {
        config(['foundation.roles.super_admin' => 'Owner']);

        Permission::create(['name' => 'Do Anything', 'guard_name' => 'web', 'module_name' => 'RolePermission']);

        (new RolePermissionDatabaseSeeder)->run();

        $this->assertTrue(Role::findByName('Owner')->hasPermissionTo('Do Anything'));
    }
}
