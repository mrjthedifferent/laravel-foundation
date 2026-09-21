<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;
use Modules\User\Services\ImpersonationService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A Super Admin can sign in as any other active user (never another Super
 * Admin), after a recent password confirmation, and return to their own
 * account. Everything done meanwhile is audited against the Super Admin and
 * tagged with the impersonated account.
 */
class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit.console' => true]);
        User::$lockedOut = [];
        $this->withoutMiddleware([PreventRequestForgery::class, ThrottleRequests::class]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(['is_active' => true, ...$attributes]);
        $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

        return $user;
    }

    private function impersonate(User $admin, User $target): TestResponse
    {
        return $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.users.impersonate', $target));
    }

    public function test_super_admin_can_impersonate_a_user(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Manager');

        $this->impersonate($admin, $target)->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($target);
        $this->assertSame($admin->id, session(ImpersonationService::SESSION_KEY));
        $this->assertDatabaseHas('audits', [
            'event' => ImpersonationService::STARTED_EVENT,
            'auditable_type' => $target->getMorphClass(),
            'auditable_id' => $target->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_impersonation_requires_a_recent_password_confirmation(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Manager');

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.impersonate', $target))
            ->assertRedirect(route('password.confirm'));

        $this->assertAuthenticatedAs($admin);
        $this->assertSame(route('admin.users.index'), session('url.intended'));
    }

    public function test_admin_without_the_super_admin_role_cannot_impersonate(): void
    {
        $admin = $this->userWithRole('Admin');
        $target = $this->userWithRole('Manager');

        $this->impersonate($admin, $target)->assertForbidden();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_super_admin_cannot_impersonate_themselves(): void
    {
        $admin = $this->userWithRole('Super Admin');

        $this->impersonate($admin, $admin)->assertForbidden();
    }

    public function test_super_admin_cannot_impersonate_another_super_admin(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $otherSuperAdmin = $this->userWithRole('Super Admin');

        $this->impersonate($admin, $otherSuperAdmin)->assertForbidden();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_inactive_user_cannot_be_impersonated(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Manager', ['is_active' => false]);

        $this->impersonate($admin, $target)->assertForbidden();
    }

    public function test_user_locked_out_by_the_projects_rule_cannot_be_impersonated(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Admin');
        User::$lockedOut = [$target->id];

        $this->impersonate($admin, $target)->assertForbidden();
    }

    public function test_impersonated_session_cannot_start_another_impersonation(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Manager');
        $other = $this->userWithRole('Manager');

        $this->impersonate($admin, $target);

        $this->post(route('admin.users.impersonate', $other))->assertForbidden();
        $this->assertAuthenticatedAs($target);
    }

    public function test_leaving_restores_the_super_admin_and_revokes_impersonation_tokens(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Manager');
        $target->createToken('own device', ['role:ess']);

        $this->impersonate($admin, $target);
        $target->createToken('ess-web · sso', ['role:ess', ImpersonationService::tokenAbility($admin->id)]);

        $this->post(route('admin.impersonation.leave'))
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session(ImpersonationService::SESSION_KEY));
        $this->assertSame(['own device'], $target->tokens()->pluck('name')->all());
        $this->assertDatabaseHas('audits', [
            'event' => ImpersonationService::ENDED_EVENT,
            'auditable_id' => $target->id,
            'user_id' => $admin->id,
            'tags' => 'impersonating:'.$target->id,
        ]);
    }

    public function test_leaving_without_impersonating_is_forbidden(): void
    {
        $admin = $this->userWithRole('Super Admin');

        $this->actingAs($admin)->post(route('admin.impersonation.leave'))->assertForbidden();
    }

    public function test_changes_made_while_impersonating_are_attributed_to_the_super_admin(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Manager');
        $other = $this->userWithRole('Manager');

        $this->impersonate($admin, $target);
        $other->update(['name' => 'Renamed While Impersonating']);

        $this->assertDatabaseHas('audits', [
            'event' => 'updated',
            'auditable_id' => $other->id,
            'user_id' => $admin->id,
            'tags' => 'impersonating:'.$target->id,
        ]);
    }

    public function test_impersonation_ends_when_the_user_loses_access(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Admin');

        $this->impersonate($admin, $target);

        User::$lockedOut = [$target->id];

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.users.index'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session(ImpersonationService::SESSION_KEY));
    }

    public function test_admin_pages_show_the_impersonation_banner(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Manager');

        $this->impersonate($admin, $target);

        $this->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Return to my account')
            ->assertSee($admin->name);
    }
}
