<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Modules\Settings\Models\Setting;
use Modules\Settings\Support\SettingsConfigApplier;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            PreventRequestForgery::class,
        ]);

        config(['foundation.two_factor.enabled' => false]);

        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'editor', 'guard_name' => 'web']);
        Permission::create(['name' => 'Edit Special Setting', 'guard_name' => 'web', 'module_name' => 'Settings']);
        $role->givePermissionTo('Edit Special Setting');

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return $overrides + [
            'two_factor_enabled' => '0',
            'password_min_length' => 8,
            'login_max_attempts' => 5,
            'session_lifetime' => 120,
            'api_token_idle_expiration_minutes' => 43200,
        ];
    }

    private function save(array $overrides = []): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_security'), $this->form($overrides))
            ->assertRedirect(route('admin.settings.special.security'))
            ->assertSessionHasNoErrors();

        app(SettingsConfigApplier::class)->apply();
    }

    public function test_authorized_user_can_view_the_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.special.security'))
            ->assertOk()
            ->assertViewIs('settings::special.security')
            ->assertViewHas('roles', ['admin', 'editor']);
    }

    public function test_the_1_6_route_names_still_work(): void
    {
        $this->actingAs($this->admin)->get(route('admin.settings.special.two_factor'))->assertOk()->assertViewIs('settings::special.security');
        $this->actingAs($this->admin)->post(route('admin.settings.special.update_two_factor'), $this->form())->assertSessionHasNoErrors();
    }

    public function test_unauthorized_user_cannot_view_or_update(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $this->get(route('admin.settings.special.security'))->assertForbidden();
        $this->post(route('admin.settings.special.update_security'), $this->form(['two_factor_enabled' => '1']))->assertForbidden();
        $this->assertDatabaseMissing('settings', ['key' => 'two_factor_enabled']);
    }

    public function test_nothing_is_seeded_so_config_decides_until_the_first_save(): void
    {
        $this->assertDatabaseMissing('settings', ['key' => 'two_factor_enabled']);
        $this->assertDatabaseMissing('settings', ['key' => 'password_min_length']);
    }

    public function test_saving_stores_the_settings_and_overrides_config(): void
    {
        $this->save([
            'two_factor_enabled' => '1',
            'two_factor_required_for_super_admins' => '1',
            'two_factor_required_roles' => ['editor'],
            'two_factor_issuer' => 'Acme Panel',
            'password_min_length' => 12,
            'password_mixed_case' => '1',
            'password_symbols' => '1',
            'login_max_attempts' => 3,
            'session_lifetime' => 30,
            'api_token_idle_expiration_minutes' => 1440,
        ]);

        $this->assertSame('editor', Setting::where('key', 'two_factor_required_roles')->first()->getRawOriginal('value'));
        $this->assertSame('Security', Setting::where('key', 'password_min_length')->first()->getAttribute('group'));

        $this->assertTrue(config('foundation.two_factor.enabled'));
        $this->assertTrue(config('foundation.two_factor.required_for_super_admins'));
        $this->assertSame(['editor'], config('foundation.two_factor.required_roles'));
        $this->assertSame('Acme Panel', config('foundation.two_factor.issuer'));
        $this->assertSame(12, config('foundation.passwords.min_length'));
        $this->assertTrue(config('foundation.passwords.mixed_case'));
        $this->assertFalse(config('foundation.passwords.numbers'));
        $this->assertTrue(config('foundation.passwords.symbols'));
        $this->assertSame(3, config('foundation.login.max_attempts'));
        $this->assertSame(30, config('session.lifetime'));
        $this->assertSame(1440, apiTokenIdleExpirationMinutes());
        $this->assertFalse(Setting::where('key', 'api_token_idle_expiration_minutes')->first()->getAttribute('is_visible'));
    }

    public function test_turning_it_off_and_clearing_roles_is_stored(): void
    {
        config(['foundation.two_factor.enabled' => true, 'foundation.two_factor.required_roles' => ['editor']]);

        $this->save();

        $this->assertFalse(config('foundation.two_factor.enabled'));
        $this->assertSame([], config('foundation.two_factor.required_roles'));
        $this->assertEmpty(config('foundation.two_factor.issuer'));
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_security'), $this->form([
                'two_factor_required_roles' => ['ghost'],
                'password_min_length' => 4,
                'login_max_attempts' => 0,
                'session_lifetime' => 1,
                'api_token_idle_expiration_minutes' => 0,
            ]))
            ->assertSessionHasErrors(['two_factor_required_roles.0', 'password_min_length', 'login_max_attempts', 'session_lifetime', 'api_token_idle_expiration_minutes']);

        $this->assertDatabaseMissing('settings', ['key' => 'two_factor_enabled']);
    }

    public function test_the_saved_password_rule_is_what_password_forms_enforce(): void
    {
        $this->save(['password_min_length' => 12, 'password_numbers' => '1']);

        $passes = fn (string $password): bool => Validator::make(['p' => $password], ['p' => Password::defaults()])->passes();

        $this->assertFalse($passes('short1'));
        $this->assertFalse($passes('long-enough-no-digits'));
        $this->assertTrue($passes('long-enough-with-1'));
    }

    public function test_the_saved_lockout_applies_to_sign_in(): void
    {
        $this->save(['login_max_attempts' => 2]);
        auth()->logout();

        User::factory()->create(['email' => 'ada@example.com', 'password' => 'right-password', 'is_active' => true]);

        foreach (range(1, 2) as $attempt) {
            $this->post(route('login'), ['login' => 'ada@example.com', 'password' => 'wrong'])->assertSessionHasErrors('login');
        }

        // Locked out: even the right password is refused (by the auth rate limiter
        // and LoginRequest, both of which read foundation.login.max_attempts).
        $this->post(route('login'), ['login' => 'ada@example.com', 'password' => 'right-password'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_enabling_two_factor_here_shows_it_on_the_profile(): void
    {
        $this->actingAs($this->admin)->get(route('admin.profile.edit'))->assertOk()->assertDontSee('id="two-factor"', false);

        $this->save(['two_factor_enabled' => '1']);

        $this->actingAs($this->admin)->get(route('admin.profile.edit'))->assertOk()->assertSee('id="two-factor"', false);
    }

    public function test_a_required_role_is_sent_to_set_it_up(): void
    {
        $this->save(['two_factor_enabled' => '1', 'two_factor_required_roles' => ['admin']]);

        $this->actingAs($this->admin->fresh())
            ->get(route('admin.settings.special.security'))
            ->assertRedirect(route('admin.profile.edit').'#two-factor');
    }
}
