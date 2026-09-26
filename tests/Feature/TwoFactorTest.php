<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;
use Mrj\Foundation\Support\TwoFactorAuthenticator;
use Mrj\Foundation\Tests\TestCase;
use Override;
use OwenIt\Auditing\Models\Audit;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TwoFactorTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class, ThrottleRequests::class]);
        config(['foundation.two_factor.enabled' => true]);
    }

    private function code(User $user, int $stepOffset = 0): string
    {
        $engine = new Google2FA;

        return $engine->oathTotp((string) $user->two_factor_secret, $engine->getTimestamp() + $stepOffset);
    }

    private function userWithTwoFactor(): User
    {
        $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);
        $user->forceFill([
            'two_factor_secret' => app(TwoFactorAuthenticator::class)->generateSecret(),
            'two_factor_recovery_codes' => ['aaaaa-bbbbb', 'ccccc-ddddd'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }

    private function confirmedPassword(): static
    {
        return $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_it_is_off_by_default(): void
    {
        config(['foundation.two_factor.enabled' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.profile.edit'))->assertOk()->assertDontSee('id="two-factor"', false);
        $this->actingAs($user)->confirmedPassword()->post(route('admin.profile.two-factor.enable'))->assertNotFound();
    }

    public function test_a_user_turns_it_on_from_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->confirmedPassword()->post(route('admin.profile.two-factor.enable'))->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->actingAs($user)->get(route('admin.profile.edit'))->assertOk()->assertSee('<svg', false);

        $this->actingAs($user)->post(route('admin.profile.two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code', null, 'twoFactor');
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user)->post(route('admin.profile.two-factor.confirm'), ['code' => $this->code($user)])
            ->assertSessionHas('two_factor_recovery_codes');
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_turning_it_on_needs_a_recent_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.profile.two-factor.enable'))->assertRedirect(route('password.confirm'));
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_signing_in_asks_for_a_code(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post(route('login'), ['login' => 'ada@example.com', 'password' => 'secret-password'])
            ->assertRedirect(route('two-factor.login'));
        $this->assertGuest();

        $this->get(route('two-factor.login'))->assertOk()->assertSee('name="code"', false);

        $this->post(route('two-factor.login'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post(route('two-factor.login'), ['code' => $this->code($user)])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_never_reaches_the_challenge(): void
    {
        $this->userWithTwoFactor();

        $this->post(route('login'), ['login' => 'ada@example.com', 'password' => 'nope'])->assertSessionHasErrors('login');
        $this->get(route('two-factor.login'))->assertRedirect(route('login'));
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $user = $this->userWithTwoFactor();
        $authenticator = app(TwoFactorAuthenticator::class);
        $code = $this->code($user);

        $this->assertTrue($authenticator->verify($user, $code));
        $this->assertFalse($authenticator->verify($user, $code));
        $this->assertFalse($authenticator->verify($user, $this->code($user, -1))); // older than the last one used
    }

    public function test_a_recovery_code_signs_in_once(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post(route('login'), ['login' => 'ada@example.com', 'password' => 'secret-password']);
        $this->post(route('two-factor.login'), ['recovery_code' => 'AAAAA-BBBBB'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(['ccccc-ddddd'], $user->fresh()->two_factor_recovery_codes);

        $this->post(route('logout'));
        $this->post(route('login'), ['login' => 'ada@example.com', 'password' => 'secret-password']);
        $this->post(route('two-factor.login'), ['recovery_code' => 'aaaaa-bbbbb'])->assertSessionHasErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_the_api_login_needs_a_code(): void
    {
        $user = $this->userWithTwoFactor();

        $this->postJson('/api/v1/login', ['id' => 'ada@example.com', 'password' => 'secret-password'])
            ->assertUnauthorized()
            ->assertJsonPath('errors.two_factor.0', 'required');

        $this->postJson('/api/v1/login', ['id' => 'ada@example.com', 'password' => 'secret-password', 'two_factor_code' => $this->code($user)])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_required_roles_must_set_it_up_first(): void
    {
        $this->seed(FoundationSeeder::class);
        config(['foundation.two_factor.required_roles' => ['Admin']]);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('Admin'));
        Role::findByName('Admin')->givePermissionTo(Permission::all());

        $this->actingAs($admin)->get('/admin/users')->assertRedirect(route('admin.profile.edit').'#two-factor');
        $this->actingAs($admin)->getJson('/api/v1/profile')->assertForbidden();
        $this->actingAs($admin)->get(route('admin.profile.edit'))->assertOk();
        $this->actingAs($admin)->confirmedPassword()->post(route('admin.profile.two-factor.enable'))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.profile.two-factor.confirm'), ['code' => $this->code($admin->fresh())]);

        $this->actingAs($admin->fresh())->get('/admin/users')->assertOk();

        // …and cannot simply turn it off again.
        $this->actingAs($admin->fresh())->confirmedPassword()->delete(route('admin.profile.two-factor.disable'))->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_super_admins_can_be_required_to_use_it(): void
    {
        config(['foundation.two_factor.required_for_super_admins' => true]);

        $this->actingAs(User::factory()->superAdmin()->create())->get('/admin/users')->assertRedirect(route('admin.profile.edit').'#two-factor');
    }

    public function test_an_administrator_can_reset_it(): void
    {
        $this->seed(FoundationSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('Admin'));
        Role::findByName('Admin')->givePermissionTo(Permission::all());
        $user = $this->userWithTwoFactor();

        $this->actingAs(User::factory()->create())->delete(route('admin.users.two-factor.reset', $user))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.users.two-factor.reset', $user))->assertSessionHas('success');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_secrets_are_encrypted_hidden_and_never_audited(): void
    {
        $user = $this->userWithTwoFactor();

        $raw = (array) DB::table('users')->where('id', $user->id)->first();
        $this->assertNotSame($user->two_factor_secret, $raw['two_factor_secret']);
        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $user->toArray());

        foreach (Audit::query()->get() as $audit) {
            $this->assertArrayNotHasKey('two_factor_secret', (array) $audit->new_values);
            $this->assertArrayNotHasKey('two_factor_recovery_codes', (array) $audit->new_values);
        }
    }
}
