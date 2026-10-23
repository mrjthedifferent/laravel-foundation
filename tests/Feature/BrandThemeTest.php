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
 * The brand theme's additions: the brand sidebar, the second custom colour, the hero stat card
 * and the charts' direct labels.
 */
class BrandThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);

        $this->seed(FoundationSeeder::class);
    }

    private function admin(): User
    {
        return User::query()->where('is_super_admin', true)->first()
            ?? User::factory()->superAdmin()->create();
    }

    public function test_the_sidebar_is_the_brand_one_unless_light_or_dark_is_chosen(): void
    {
        config(['settings.theme_sidebar_color.value' => null]);
        $this->assertSame('brand', app(ThemeResolver::class)->resolve()['sidebarColor']);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()->assertSee('sidebar sidebar-brand', false);

        foreach (['light', 'dark'] as $color) {
            config(['settings.theme_sidebar_color.value' => $color]);

            $this->actingAs($this->admin())->get(route('admin.dashboard'))
                ->assertOk()->assertSee('sidebar sidebar-'.$color, false);
        }

        config(['settings.theme_sidebar_color.value' => 'neon']);
        $this->assertSame('brand', app(ThemeResolver::class)->resolve()['sidebarColor']);
    }

    public function test_the_theme_page_saves_the_brand_sidebar_and_a_second_custom_colour(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.settings.special.theme'))
            ->assertOk()
            ->assertSee('value="brand"', false)
            ->assertSee('name="theme_custom_color_2"', false);

        $this->actingAs($admin)->post(route('admin.settings.special.update_theme'), [
            'theme_color_palette' => 'custom',
            'theme_custom_color' => '#0f766e',
            'theme_custom_color_2' => '#06b6d4',
            'theme_sidebar_color' => 'brand',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'theme_sidebar_color', 'value' => 'brand']);
        $this->assertDatabaseHas('settings', ['key' => 'theme_custom_color_2', 'value' => '#06b6d4']);

        // Not a hex: stored blank, so the gradient's second colour is mixed from the first.
        $this->actingAs($admin)->post(route('admin.settings.special.update_theme'), [
            'theme_custom_color_2' => 'cyan',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'theme_custom_color_2', 'value' => '']);
    }

    public function test_the_second_custom_colour_reaches_the_page_only_when_set(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()->assertDontSee('--custom-primary-2', false);

        config(['settings.theme_custom_color_2.value' => '#06b6d4']);

        $this->assertSame('#06b6d4', app(ThemeResolver::class)->resolve()['customColor2']);
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()->assertSee('--custom-primary-2: #06b6d4;', false);

        config(['settings.theme_custom_color_2.value' => 'red; } body { display: none']);
        $this->assertSame('', app(ThemeResolver::class)->resolve()['customColor2']);
    }

    public function test_a_stat_card_can_be_the_hero(): void
    {
        $this->assertStringContainsString('fd-stat-hero', Blade::render('<x-stat-card label="Users" value="12" hero />'));
        $this->assertStringNotContainsString('fd-stat-hero', Blade::render('<x-stat-card label="Users" value="12" />'));
    }

    public function test_the_dashboard_makes_its_first_stat_the_hero(): void
    {
        $html = view('dashboard.widgets.stats', ['stats' => [
            ['label' => 'Users', 'value' => 12],
            ['label' => 'Roles', 'value' => 3],
            ['label' => 'Logs', 'value' => 40, 'hero' => true],
        ]])->render();

        $this->assertSame(2, substr_count($html, 'fd-stat-hero'));

        $html = view('dashboard.widgets.stats', ['stats' => [['label' => 'Users', 'value' => 12, 'hero' => false]]])->render();
        $this->assertStringNotContainsString('fd-stat-hero', $html);
    }

    public function test_the_bar_chart_writes_each_value_at_the_end_of_its_bar(): void
    {
        $html = Blade::render('<x-chart-bar :series="[\'Created\' => 40, \'Updated\' => 10]" label="Events" />');

        $this->assertStringContainsString('fd-bars is-labelled', $html);
        $this->assertStringContainsString('--fd-bar: 1', $html);
        $this->assertStringContainsString('--fd-bar: 0.25', $html);
        $this->assertMatchesRegularExpression('/fd-bars-fill"><\/span><span class="fd-bars-value">40</', $html);

        $plain = Blade::render('<x-chart-bar :series="[\'Created\' => 40]" label="Events" :labels="false" />');
        $this->assertStringNotContainsString('is-labelled', $plain);
        $this->assertStringContainsString('width: 100%', $plain);
    }

    public function test_the_area_chart_writes_each_lines_last_value_at_its_end(): void
    {
        $html = Blade::render('<x-chart-area :series="[\'2026-09-01\' => 10, \'2026-09-02\' => 1234]" :previous="[\'2026-08-30\' => 5, \'2026-08-31\' => 300]" label="Sign-ins" />');

        $this->assertStringContainsString('fd-chart-end is-current', $html);
        $this->assertStringContainsString('>1,234</span>', $html);
        $this->assertStringContainsString('fd-chart-end is-previous', $html);
        $this->assertStringContainsString('>300</span>', $html);

        $plain = Blade::render('<x-chart-area :series="[\'2026-09-01\' => 10]" label="Sign-ins" :labels="false" />');
        $this->assertStringNotContainsString('fd-chart-end', $plain);
    }
}
