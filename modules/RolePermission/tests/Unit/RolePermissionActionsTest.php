<?php

namespace Modules\RolePermission\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\RolePermission\Actions\AssignPermissionAction;
use Modules\RolePermission\Actions\CloneRoleAction;
use Modules\RolePermission\Actions\DeletePermissionAction;
use Modules\RolePermission\Actions\StorePermissionAction;
use Modules\RolePermission\Actions\StoreRoleAction;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionActionsTest extends TestCase
{
    use RefreshDatabase;

    private function permission(string $name): Permission
    {
        return Permission::query()->create(['name' => $name, 'guard_name' => 'web', 'module_name' => 'Test']);
    }

    public function test_clone_copies_the_permissions_but_not_the_users(): void
    {
        $this->permission('View Post');
        $this->permission('Edit Post');
        $role = Role::query()->create(['name' => 'Editor', 'guard_name' => 'web']);
        $role->givePermissionTo(['View Post', 'Edit Post']);
        User::factory()->create()->assignRole($role);

        $clone = app(CloneRoleAction::class)->execute($role);

        $this->assertSame('Editor - Copy', $clone->name);
        $this->assertSame('web', $clone->guard_name);
        $this->assertEqualsCanonicalizing(['View Post', 'Edit Post'], $clone->permissions->pluck('name')->all());
        $this->assertSame(0, User::role('Editor - Copy')->count());
        $this->assertEqualsCanonicalizing(['View Post', 'Edit Post'], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_cloning_repeatedly_numbers_the_copies(): void
    {
        $role = Role::query()->create(['name' => 'Editor', 'guard_name' => 'web']);
        $action = app(CloneRoleAction::class);

        $names = [
            $action->execute($role)->name,
            $action->execute($role)->name,
            $action->execute($role)->name,
        ];

        $this->assertSame(['Editor - Copy', 'Editor - Copy 2', 'Editor - Copy 3'], $names);
    }

    public function test_store_actions_use_the_configured_web_guard(): void
    {
        config(['foundation.guards.web' => 'web']);

        $role = app(StoreRoleAction::class)->execute('Auditor');
        $permission = app(StorePermissionAction::class)->execute('Export Report', 'Reports', 'Download reports as spreadsheets');

        $this->assertSame('web', $role->guard_name);
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'Export Report',
            'module_name' => 'Reports',
            'description' => 'Download reports as spreadsheets',
            'guard_name' => 'web',
        ]);
    }

    public function test_assign_replaces_the_role_permissions(): void
    {
        $this->permission('View Post');
        $this->permission('Edit Post');
        $role = Role::query()->create(['name' => 'Editor', 'guard_name' => 'web']);
        $role->givePermissionTo('View Post');

        app(AssignPermissionAction::class)->execute($role, ['Edit Post']);

        $this->assertSame(['Edit Post'], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_assigning_an_unknown_permission_throws_and_keeps_the_existing_ones(): void
    {
        $this->permission('View Post');
        $role = Role::query()->create(['name' => 'Editor', 'guard_name' => 'web']);
        $role->givePermissionTo('View Post');

        try {
            app(AssignPermissionAction::class)->execute($role, ['No Such Permission']);
            $this->fail('Expected PermissionDoesNotExist.');
        } catch (PermissionDoesNotExist) {
            // expected
        }

        $this->assertSame(['View Post'], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_deleting_a_permission_revokes_it_from_every_role_and_user(): void
    {
        $permission = $this->permission('Delete Post');
        $roles = [
            Role::query()->create(['name' => 'Editor', 'guard_name' => 'web']),
            Role::query()->create(['name' => 'Moderator', 'guard_name' => 'web']),
        ];
        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }
        $user = User::factory()->create();
        $user->assignRole($roles[0]);

        app(DeletePermissionAction::class)->execute($permission);

        $this->assertModelMissing($permission);
        $this->assertDatabaseMissing('role_has_permissions', ['permission_id' => $permission->id]);
        $this->assertFalse($user->fresh()->checkPermissionTo('Delete Post'));
    }
}
