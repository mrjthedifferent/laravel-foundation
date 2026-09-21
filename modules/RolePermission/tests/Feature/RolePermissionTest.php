<?php

namespace Modules\RolePermission\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([ValidateCsrfToken::class]);

        foreach (['View Role', 'Create Role', 'Edit Role', 'Delete Role', 'Assign Permission'] as $perm) {
            Permission::updateOrCreate(['name' => $perm, 'guard_name' => 'web'], ['module_name' => 'Role Management']);
        }

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->givePermissionTo(['View Role', 'Create Role', 'Edit Role', 'Delete Role', 'Assign Permission']);
    }

    // ── Role CRUD ─────────────────────────────────────────────────────────────

    public function test_admin_can_list_roles(): void
    {
        Role::create(['name' => 'Editor', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->get(route('admin.role.index'))
            ->assertOk()
            ->assertViewIs('rolepermission::index');
    }

    public function test_admin_can_create_role(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.role.store'), ['role_name' => 'Moderator'])
            ->assertRedirect(route('admin.role.index'));

        $this->assertDatabaseHas('roles', ['name' => 'Moderator']);
    }

    public function test_store_validates_unique_role_name(): void
    {
        Role::create(['name' => 'Editor', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->post(route('admin.role.store'), ['role_name' => 'Editor'])
            ->assertSessionHasErrors('role_name');
    }

    public function test_admin_can_update_role(): void
    {
        $role = Role::create(['name' => 'OldName', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->put(route('admin.role.update', $role), ['role_name' => 'NewName'])
            ->assertRedirect(route('admin.role.index'));

        $this->assertEquals('NewName', $role->fresh()->name);
    }

    public function test_admin_can_delete_role(): void
    {
        $role = Role::create(['name' => 'ToDelete', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->delete(route('admin.role.destroy', $role))
            ->assertRedirect(route('admin.role.index'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admin_can_clone_role_with_permissions(): void
    {
        $role = Role::create(['name' => 'Original', 'guard_name' => 'web']);
        $role->givePermissionTo('View Role');

        $this->actingAs($this->admin)
            ->get(route('admin.role.clone', $role))
            ->assertRedirect(route('admin.role.index'));

        $clone = Role::where('name', 'Original - Copy')->first();
        $this->assertNotNull($clone);
        $this->assertTrue($clone->hasPermissionTo('View Role'));
    }

    // ── Assign permissions ────────────────────────────────────────────────────

    public function test_admin_can_assign_permissions_to_role(): void
    {
        $role = Role::create(['name' => 'Writer', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->post(route('admin.role.assign.permission', $role), ['permissions' => ['View Role']])
            ->assertRedirect();

        $this->assertTrue($role->fresh()->hasPermissionTo('View Role'));
    }

    public function test_assign_with_empty_permissions_revokes_all(): void
    {
        $role = Role::create(['name' => 'Writer', 'guard_name' => 'web']);
        $role->givePermissionTo('View Role');

        $this->actingAs($this->admin)
            ->post(route('admin.role.assign.permission', $role), ['permissions' => []])
            ->assertRedirect();

        $this->assertFalse($role->fresh()->hasPermissionTo('View Role'));
    }

    // ── Permission management ─────────────────────────────────────────────────

    public function test_admin_can_view_manage_permissions_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.permissions.manage'))
            ->assertOk()
            ->assertViewIs('rolepermission::manage_permissions');
    }

    public function test_admin_can_create_permission(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.permission.store'), [
                'permission_name' => 'Publish Post',
                'module_name' => 'Blog',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('permissions', ['name' => 'Publish Post', 'module_name' => 'Blog']);
    }

    public function test_admin_can_delete_permission(): void
    {
        $permission = Permission::create(['name' => 'Temp Perm', 'guard_name' => 'web', 'module_name' => 'Test']);

        $this->actingAs($this->admin)
            ->delete(route('admin.permission.delete', $permission))
            ->assertRedirect();

        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_delete_permission_revokes_from_roles(): void
    {
        $role = Role::create(['name' => 'Tester', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'Do Thing', 'guard_name' => 'web', 'module_name' => 'Test']);
        $role->givePermissionTo($permission);

        $this->actingAs($this->admin)
            ->delete(route('admin.permission.delete', $permission))
            ->assertRedirect();

        $this->assertDatabaseMissing('role_has_permissions', ['role_id' => $role->id, 'permission_id' => $permission->id]);
    }

    public function test_get_permission_roles_returns_json(): void
    {
        $role = Role::create(['name' => 'Viewer', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'See Things', 'guard_name' => 'web', 'module_name' => 'Test']);
        $role->givePermissionTo($permission);

        $this->actingAs($this->admin)
            ->getJson(route('admin.permission.roles', $permission))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.roles');
    }

    public function test_user_without_permission_cannot_access_role_index(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.role.index'))
            ->assertForbidden();
    }
}
