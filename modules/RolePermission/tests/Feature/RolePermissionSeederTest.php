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

        $this->assertDatabaseHas('roles', ['name' => 'Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'User']);
    }

    public function test_it_seeds_a_projects_configured_role_names(): void
    {
        config(['foundation.roles' => [
            'admin' => 'Staff',
            'user' => 'Member',
        ]]);

        (new RolePermissionDatabaseSeeder)->run();

        $this->assertDatabaseHas('roles', ['name' => 'Staff']);
        $this->assertDatabaseHas('roles', ['name' => 'Member']);
        $this->assertDatabaseMissing('roles', ['name' => 'Admin']);
    }

    /**
     * Super Admin is a flag on the user, not a role: nothing seeds one, and no role is
     * handed every permission (which anyone able to edit roles could then strip).
     */
    public function test_no_super_admin_role_is_seeded_and_no_role_gets_every_permission(): void
    {
        Permission::create(['name' => 'Do Anything', 'guard_name' => 'web', 'module_name' => 'RolePermission']);

        (new RolePermissionDatabaseSeeder)->run();

        $this->assertDatabaseMissing('roles', ['name' => 'Super Admin']);
        $this->assertFalse(Role::query()->get()->contains(fn (Role $role): bool => $role->hasPermissionTo('Do Anything')));
    }
}
