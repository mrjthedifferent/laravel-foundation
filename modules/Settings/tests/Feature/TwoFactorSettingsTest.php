<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Modules\Settings\Support\SettingsConfigApplier;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TwoFactorSettingsTest extends TestCase
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

    private function applySettings(): void
    {
        app(SettingsConfigApplier::class)->apply();
    }

    public function test_authorized_user_can_view_the_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.special.two_factor'))
            ->assertOk()
            ->assertViewIs('settings::special.two-factor')
            ->assertViewHas('roles', ['admin', 'editor'])
            ->assertViewHas('enabled', false);
    }

    public function test_unauthorized_user_cannot_view_or_update(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $this->get(route('admin.settings.special.two_factor'))->assertForbidden();
        $this->post(route('admin.settings.special.update_two_factor'), ['enabled' => '1'])->assertForbidden();
        $this->assertDatabaseMissing('settings', ['key' => 'two_factor_enabled']);
    }

    public function test_saving_stores_the_settings_and_overrides_config(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_two_factor'), [
                'enabled' => '1',
                'required_for_super_admins' => '1',
                'required_roles' => ['editor'],
                'issuer' => 'Acme Panel',
            ])
            ->assertRedirect(route('admin.settings.special.two_factor'))
            ->assertSessionHas('success');

        $this->assertSame('1', Setting::where('key', 'two_factor_enabled')->first()->getRawOriginal('value'));
        $this->assertSame('editor', Setting::where('key', 'two_factor_required_roles')->first()->getRawOriginal('value'));

        $this->applySettings();

        $this->assertTrue(config('foundation.two_factor.enabled'));
        $this->assertTrue(config('foundation.two_factor.required_for_super_admins'));
        $this->assertSame(['editor'], config('foundation.two_factor.required_roles'));
        $this->assertSame('Acme Panel', config('foundation.two_factor.issuer'));
    }

    public function test_turning_it_off_and_clearing_roles_is_stored(): void
    {
        config(['foundation.two_factor.enabled' => true, 'foundation.two_factor.required_roles' => ['editor']]);

        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_two_factor'), ['enabled' => '0'])
            ->assertRedirect();

        $this->applySettings();

        $this->assertFalse(config('foundation.two_factor.enabled'));
        $this->assertSame([], config('foundation.two_factor.required_roles'));
        $this->assertEmpty(config('foundation.two_factor.issuer'));
    }

    public function test_an_unknown_role_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_two_factor'), ['enabled' => '1', 'required_roles' => ['ghost']])
            ->assertSessionHasErrors('required_roles.0');

        $this->assertDatabaseMissing('settings', ['key' => 'two_factor_enabled']);
    }

    public function test_enabling_it_here_shows_it_on_the_profile(): void
    {
        $this->actingAs($this->admin)->get(route('admin.profile.edit'))->assertOk()->assertDontSee('id="two-factor"', false);

        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_two_factor'), ['enabled' => '1'])
            ->assertRedirect();
        $this->applySettings();

        $this->actingAs($this->admin)->get(route('admin.profile.edit'))->assertOk()->assertSee('id="two-factor"', false);
    }

    public function test_a_required_role_is_sent_to_set_it_up(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_two_factor'), ['enabled' => '1', 'required_roles' => ['admin']])
            ->assertRedirect();
        $this->applySettings();

        $this->actingAs($this->admin->fresh())
            ->get(route('admin.settings.special.two_factor'))
            ->assertRedirect(route('admin.profile.edit').'#two-factor');
    }
}
