<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Modules\Settings\Support\SettingsConfigApplier;
use Mrj\Foundation\Contracts\SettingsRepository;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LoginPageSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);
    }

    private function editor(): User
    {
        Permission::updateOrCreate(['name' => 'Edit Special Setting', 'guard_name' => 'web'], ['module_name' => 'Settings']);

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('Edit Special Setting');

        return $user;
    }

    /** What the next request would see: the saved settings applied over config(). */
    private function reapply(): void
    {
        app(SettingsRepository::class)->forget();
        app(SettingsConfigApplier::class)->apply();
    }

    public function test_the_package_ships_the_panel_defaults(): void
    {
        $this->assertSame('bars', config('foundation-login.graphic'));
        $this->assertSame('auto', config('foundation-login.mark'));
        $this->assertCount(3, config('foundation-login.highlights'));
    }

    public function test_the_sign_in_page_draws_the_panel_from_config(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-graphic="bars"', false)
            ->assertSee('fd-auth-mobile-brand', false)
            ->assertSee(__('foundation::foundation.auth.tagline'))
            ->assertSee(__('foundation::foundation.auth.highlight_access'));

        config([
            'foundation-login.headline' => 'Run :app from anywhere.',
            'foundation-login.highlights' => [],
            'foundation-login.graphic' => 'unknown',
            'foundation-login.mark' => 'none',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Run '.mailAppName().' from anywhere.')
            ->assertDontSee('fd-auth-points', false)
            ->assertDontSee('fd-brand-mark', false)
            ->assertSee('data-graphic="bars"', false);
    }

    public function test_a_permitted_user_can_open_the_page(): void
    {
        $this->actingAs($this->editor())
            ->get(route('admin.settings.special.login_page'))
            ->assertOk()
            ->assertSee(__('settings::settings.special_login_page.title'))
            ->assertSee('data-login-preview', false);
    }

    public function test_a_user_without_the_permission_is_refused(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.settings.special.login_page'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.settings.special.update_login_page'), [])->assertForbidden();
    }

    public function test_saved_settings_reach_the_sign_in_page(): void
    {
        $this->actingAs($this->editor())->post(route('admin.settings.special.update_login_page'), [
            'headline' => 'Run :app from anywhere.',
            'subheadline' => '',
            'highlights_mode' => 'custom',
            'highlights' => [
                ['icon' => 'ph ph-rocket-launch', 'text' => 'Launch in a day'],
                ['icon' => 'ph ph-check', 'text' => ''],   // skipped: no text
            ],
            'mark' => 'monogram',
            'show_name' => '1',
            'graphic' => 'glow',
        ])->assertRedirect(route('admin.settings.special.login_page'))->assertSessionHasNoErrors();

        $this->assertSame([['icon' => 'ph ph-rocket-launch', 'text' => 'Launch in a day']],
            Setting::where('key', 'login_panel_highlights')->first()->value);
        $this->assertNull(Setting::where('key', 'login_panel_subheadline')->first(), 'a blank text stores nothing');

        auth()->logout();
        $this->reapply();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Run '.mailAppName().' from anywhere.')
            ->assertSee('Launch in a day')
            ->assertSee('ph ph-rocket-launch', false)
            ->assertSee('data-graphic="glow"', false)
            ->assertSee(__('foundation::foundation.auth.tagline_meta', ['app' => mailAppName()]));
    }

    public function test_going_back_to_the_defaults_removes_the_saved_values(): void
    {
        $editor = $this->editor();
        $payload = ['highlights_mode' => 'custom', 'highlights' => [], 'mark' => 'auto', 'graphic' => 'none', 'headline' => 'Custom'];

        $this->actingAs($editor)->post(route('admin.settings.special.update_login_page'), $payload);
        $this->reapply();
        $this->assertSame([], config('foundation-login.highlights'), 'an empty custom list hides the bullets');

        $this->actingAs($editor)->post(route('admin.settings.special.update_login_page'),
            ['headline' => '', 'highlights_mode' => 'default'] + $payload);

        $this->assertNull(Setting::where('key', 'login_panel_headline')->first());
        $this->assertNull(Setting::where('key', 'login_panel_highlights')->first());

        $this->reapply();
        $this->assertCount(3, config('foundation-login.highlights'), 'the config file\'s bullets show again');
    }

    public function test_an_icon_must_be_a_phosphor_class(): void
    {
        $this->actingAs($this->editor())->post(route('admin.settings.special.update_login_page'), [
            'highlights_mode' => 'custom',
            'highlights' => [['icon' => '<script>', 'text' => 'Hi']],
            'mark' => 'auto',
            'graphic' => 'bars',
        ])->assertSessionHasErrors('highlights.0.icon');
    }
}
