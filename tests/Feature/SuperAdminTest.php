<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Gate;
use Modules\Notification\Models\Notification;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;
use Mrj\Foundation\Tests\TestCase;
use Override;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Super Admin is a flag, not a role: it passes every permission check, nothing in the
 * web or API layer can grant it, and no one but the console can act on such an account
 * beyond editing its profile.
 */
class SuperAdminTest extends TestCase
{
    private User $superAdmin;

    /** An ordinary administrator holding every permission there is. */
    private User $admin;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);
        $this->seed(FoundationSeeder::class);

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findByName('Admin'));
        Role::findByName('Admin')->givePermissionTo(Permission::all());
    }

    public function test_a_super_admin_with_no_role_passes_every_permission_check(): void
    {
        $this->assertSame([], $this->superAdmin->getRoleNames()->all());

        foreach (Permission::pluck('name') as $permission) {
            $this->assertTrue($this->superAdmin->can($permission), $permission);
        }

        $this->actingAs($this->superAdmin)->get(route('admin.role.index'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('admin.users.index'))->assertOk();
    }

    /**
     * Only permission checks are bypassed: a policy's own rules (ownership, state) still apply.
     */
    public function test_policy_rules_still_apply_to_a_super_admin(): void
    {
        $someoneElses = new Notification(['notifiable_type' => $this->admin->getMorphClass(), 'notifiable_id' => $this->admin->id]);

        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('view', $someoneElses));
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('delete', $this->superAdmin));
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('leaveImpersonation', User::class));
    }

    public function test_an_admin_with_every_permission_cannot_touch_a_super_admin(): void
    {
        $target = $this->superAdmin;
        $as = $this->actingAs($this->admin);

        $as->get(route('admin.users.show', $target))->assertOk();
        $as->put(route('admin.users.update', $target), ['name' => 'Renamed', 'email' => $target->email, 'roles' => [Role::findByName('User')->id]])->assertForbidden();
        $as->post(route('admin.users.status', $target), ['is_active' => 0])->assertForbidden();
        $as->delete(route('admin.users.destroy', $target))->assertForbidden();
        $as->get(route('admin.user.password.reset', $target))->assertForbidden();
        $as->post(route('admin.users.account.manage', $target), ['action' => 'delete'])->assertForbidden();
        $as->post(route('admin.users.verify.email', $target))->assertForbidden();

        $target->refresh();
        $this->assertTrue($target->isSuperAdmin());
        $this->assertTrue($target->is_active);
        $this->assertNotSame('Renamed', $target->name);
    }

    public function test_a_super_admin_may_edit_another_super_admins_profile_and_nothing_more(): void
    {
        $other = User::factory()->superAdmin()->create(['name' => 'Other']);
        $as = $this->actingAs($this->superAdmin);

        $as->put(route('admin.users.update', $other), ['name' => 'Renamed', 'email' => $other->email, 'roles' => [Role::findByName('User')->id]])
            ->assertRedirect();
        $this->assertSame('Renamed', $other->fresh()->name);

        $as->post(route('admin.users.status', $other), ['is_active' => 0])->assertForbidden();
        $as->delete(route('admin.users.destroy', $other))->assertForbidden();
        $as->get(route('admin.user.password.reset', $other))->assertForbidden();
        $as->post(route('admin.users.account.manage', $other), ['action' => 'delete'])->assertForbidden();
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('impersonate', $other));

        $this->assertTrue($other->fresh()->is_active);
    }

    public function test_a_super_admin_still_manages_ordinary_accounts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->superAdmin)->post(route('admin.users.status', $user), ['is_active' => 0])->assertRedirect();

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_no_request_can_set_the_flag(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'name' => 'New Person',
            'email' => 'new.person@example.com',
            'password' => 'secret-123',
            'password_confirmation' => 'secret-123',
            'roles' => [Role::findByName('User')->id],
            'is_super_admin' => 1,
        ])->assertRedirect();
        $this->assertFalse(User::where('email', 'new.person@example.com')->sole()->isSuperAdmin());

        $user = User::factory()->create();
        $this->actingAs($this->superAdmin)->put(route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'roles' => [Role::findByName('User')->id], 'is_super_admin' => 1,
        ]);
        $this->actingAs($user)->patch(route('admin.profile.update'), ['name' => 'Me', 'email' => $user->email, 'is_super_admin' => 1]);
        $this->actingAs($user, 'sanctum')->patchJson('/api/'.config('foundation.routing.api_prefix').'/profile', ['name' => 'Me', 'is_super_admin' => true]);

        $this->assertFalse($user->fresh()->isSuperAdmin());
    }
}
