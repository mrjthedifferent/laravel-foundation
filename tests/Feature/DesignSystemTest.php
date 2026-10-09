<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;
use Mrj\Foundation\Services\ThemeResolver;
use Mrj\Foundation\Tests\TestCase;

/**
 * Page width, density and corner presets, and the shared pieces every page now builds on:
 * the page header, the filter bar, the file drop zone, skeletons, empty states, bottom navigation.
 */
class DesignSystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Rendering a component directly bypasses the middleware that shares $errors with views.
        View::share('errors', new ViewErrorBag);

        $this->seed(FoundationSeeder::class);
    }

    private function admin(): User
    {
        return User::query()->where('is_super_admin', true)->first()
            ?? User::factory()->superAdmin()->create();
    }

    public function test_the_theme_resolves_width_density_and_radius_with_safe_defaults(): void
    {
        $theme = app(ThemeResolver::class)->resolve();

        $this->assertSame('fluid', $theme['contentWidth']);
        $this->assertSame('comfortable', $theme['density']);
        $this->assertSame('rounded', $theme['radius']);

        config([
            'settings.theme_content_width.value' => 'boxed',
            'settings.theme_density.value' => 'compact',
            'settings.theme_radius.value' => 'soft',
        ]);
        $theme = app(ThemeResolver::class)->resolve();

        $this->assertSame(['boxed', 'compact', 'soft'], [$theme['contentWidth'], $theme['density'], $theme['radius']]);
        $this->assertSame('boxed', $theme['windowTheme']['contentWidth']);

        config(['settings.theme_content_width.value' => 'enormous', 'settings.theme_radius.value' => ['x']]);
        $theme = app(ThemeResolver::class)->resolve();

        $this->assertSame('fluid', $theme['contentWidth']);
        $this->assertSame('rounded', $theme['radius']);
    }

    public function test_the_saved_preset_reaches_the_html_element(): void
    {
        config([
            'settings.theme_content_width.value' => 'boxed',
            'settings.theme_density.value' => 'compact',
            'settings.theme_radius.value' => 'sharp',
        ]);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-width="boxed"', false)
            ->assertSee('data-density="compact"', false)
            ->assertSee('data-radius="sharp"', false);
    }

    public function test_the_theme_page_saves_the_presets_and_ignores_values_it_does_not_offer(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.settings.special.theme'))
            ->assertOk()
            ->assertSee('name="theme_content_width"', false)
            ->assertSee('name="theme_density"', false)
            ->assertSee('name="theme_radius"', false);

        $this->actingAs($admin)->post(route('admin.settings.special.update_theme'), [
            'theme_content_width' => 'boxed',
            'theme_density' => 'compact',
            'theme_radius' => 'wavy',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'theme_content_width', 'value' => 'boxed']);
        $this->assertDatabaseHas('settings', ['key' => 'theme_density', 'value' => 'compact']);
        // An option that does not exist falls back to the default instead of being stored.
        $this->assertDatabaseHas('settings', ['key' => 'theme_radius', 'value' => 'rounded']);
    }

    public function test_the_page_header_shows_title_subtitle_icon_actions_and_tabs(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-page-header title="Security" subtitle="Sign-in rules" icon="ph-shield">
                <x-slot name="actions"><button>Save</button></x-slot>
                <x-slot name="tabs"><a href="#">One</a></x-slot>
            </x-page-header>
            BLADE);

        $this->assertStringContainsString('<h1 class="fd-page-title">Security</h1>', $html);
        $this->assertStringContainsString('Sign-in rules', $html);
        $this->assertStringContainsString('ph-shield', $html);
        $this->assertStringContainsString('<button>Save</button>', $html);
        $this->assertStringContainsString('fd-page-tabs', $html);
    }

    public function test_the_filter_bar_wraps_the_fields_a_page_gives_it(): void
    {
        $html = Blade::render('<x-search-card><div class="col-span-12"><input name="search"></div></x-search-card>');

        $this->assertStringContainsString('data-fd-filterbar', $html);
        $this->assertStringContainsString('data-fd-filter-toggle', $html);
        $this->assertStringContainsString('data-fd-filter-panel', $html);
        $this->assertStringContainsString('name="search"', $html);
        $this->assertStringContainsString(__('foundation::foundation.common.filters'), $html);
    }

    public function test_the_file_input_is_a_drop_zone_around_a_real_input(): void
    {
        $html = Blade::render('<x-form.file name="avatar" label="Photo" accept="image/png" current="/img/a.png" help="Max 2 MB" />');

        $this->assertStringContainsString('data-fd-upload', $html);
        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('name="avatar"', $html);
        $this->assertStringContainsString('accept="image/png"', $html);
        $this->assertStringContainsString('src="/img/a.png"', $html);
        $this->assertStringContainsString('Max 2 MB', $html);
    }

    public function test_skeleton_and_empty_state_render(): void
    {
        $skeleton = Blade::render('<x-skeleton :rows="2" />');
        $this->assertSame(2, substr_count($skeleton, 'fd-skeleton-row'));
        $this->assertStringContainsString('aria-hidden="true"', $skeleton);

        $empty = Blade::render('<x-empty-state icon="ph-users" title="No users" text="Add one">Go</x-empty-state>');
        $this->assertStringContainsString('No users', $empty);
        $this->assertStringContainsString('Add one', $empty);
        $this->assertStringContainsString('fd-empty-action', $empty);

        $this->assertStringNotContainsString('fd-empty-action', Blade::render('<x-empty-state title="Nothing" />'));
    }

    public function test_the_pages_carry_the_bottom_navigation_and_tables_can_stack(): void
    {
        $this->actingAs($this->admin())->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('fd-bottomnav', false)
            ->assertSee('table-stack', false)
            ->assertSee('data-fd-filterbar', false);
    }

    public function test_the_profile_page_is_a_sectioned_layout_with_a_drop_zone(): void
    {
        $this->actingAs($this->admin())->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('fd-form-nav', false)
            ->assertSee('id="profile-information"', false)
            ->assertSee('id="profile-password"', false)
            ->assertSee('id="profile-danger"', false)
            ->assertSee('data-fd-upload', false);
    }
}
