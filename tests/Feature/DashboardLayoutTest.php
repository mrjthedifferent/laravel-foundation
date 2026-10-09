<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;
use Mrj\Foundation\Models\DashboardLayout;
use Mrj\Foundation\Services\Dashboard\DashboardLayoutService;
use Mrj\Foundation\Services\Dashboard\WidgetRegistry;
use Mrj\Foundation\Tests\TestCase;

/**
 * Each viewer's own arrangement of the dashboard: saved per user, merged with whatever
 * is registered now, and never able to corrupt the page.
 */
class DashboardLayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FoundationSeeder::class);
    }

    private function admin(): User
    {
        return User::query()->where('is_super_admin', true)->first()
            ?? User::factory()->superAdmin()->create();
    }

    private function layouts(): DashboardLayoutService
    {
        return app(DashboardLayoutService::class);
    }

    /**
     * @param  list<array{key: string, width: int, hidden: bool}>  $layout
     * @return list<string>
     */
    private function keys(array $layout): array
    {
        return array_column($layout, 'key');
    }

    public function test_a_viewer_who_never_arranged_anything_gets_every_widget_in_its_default_place(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $layout = $this->layouts()->resolve($admin);

        $this->assertSame(array_keys(app(WidgetRegistry::class)->all()), $this->keys($layout));
        $this->assertContains('stats', $this->keys($layout));
        $this->assertContains('trend', $this->keys($layout));
        $this->assertContains('health', $this->keys($layout));
        $this->assertContains('user-status', $this->keys($layout));
        $this->assertSame(0, DashboardLayout::query()->count());

        foreach ($layout as $slot) {
            $this->assertFalse($slot['hidden']);
        }

        $stats = collect($layout)->firstWhere('key', 'stats');
        $this->assertSame(12, $stats['width']);
    }

    public function test_a_saved_layout_is_applied_in_order_with_its_widths_and_hidden_flags(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->layouts()->save($admin, [
            ['key' => 'health', 'width' => 6, 'hidden' => false],
            ['key' => 'stats', 'width' => 12, 'hidden' => true],
        ]);

        $layout = $this->layouts()->resolve($admin);

        $this->assertSame(['health', 'stats'], array_slice($this->keys($layout), 0, 2));
        $this->assertSame(6, $layout[0]['width']);
        $this->assertTrue($layout[1]['hidden']);
        // Everything not mentioned is still there, appended at its default place.
        $this->assertContains('trend', $this->keys($layout));
    }

    public function test_layouts_are_per_user(): void
    {
        $admin = $this->admin();
        $other = User::factory()->superAdmin()->create();
        $this->actingAs($admin);

        $this->layouts()->save($admin, [['key' => 'health', 'width' => 3, 'hidden' => true]]);

        $this->assertSame('health', $this->layouts()->resolve($admin)[0]['key']);
        $this->assertNotSame('health', $this->layouts()->resolve($other)[0]['key']);
        $this->assertSame(1, DashboardLayout::query()->count());
    }

    public function test_unknown_widgets_duplicates_and_odd_widths_are_dropped_or_defaulted(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $saved = $this->layouts()->save($admin, [
            ['key' => 'no-such-widget', 'width' => 6, 'hidden' => false],
            ['key' => 'health', 'width' => 7, 'hidden' => false],
            ['key' => 'health', 'width' => 3, 'hidden' => true],
            ['hidden' => true],
            'not even an array',
        ]);

        $this->assertNotContains('no-such-widget', $this->keys($saved));
        $this->assertSame(1, count(array_filter($saved, fn (array $slot): bool => $slot['key'] === 'health')));

        $health = collect($saved)->firstWhere('key', 'health');
        // 7 is not a width on offer: the widget's own default applies, and the duplicate is ignored.
        $this->assertSame(4, $health['width']);
        $this->assertFalse($health['hidden']);
    }

    public function test_a_saved_layout_survives_a_widget_that_is_no_longer_registered(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        // As left behind when a module is disabled after the viewer arranged the page.
        DashboardLayout::query()->create(['user_id' => $admin->id, 'layout' => [
            ['key' => 'module-that-was-disabled', 'width' => 6, 'hidden' => false],
            ['key' => 'health', 'width' => 6, 'hidden' => false],
        ]]);

        $keys = $this->keys($this->layouts()->resolve($admin));

        $this->assertNotContains('module-that-was-disabled', $keys);
        $this->assertSame('health', $keys[0]);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_the_page_draws_widgets_in_the_saved_order_and_width_and_leaves_hidden_ones_unrendered(): void
    {
        $admin = $this->admin();
        $this->layouts()->save($admin, [
            ['key' => 'health', 'width' => 6, 'hidden' => false],
            ['key' => 'stats', 'width' => 12, 'hidden' => true],
        ]);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'data-key="stats"'), strpos($html, 'data-key="health"'));
        $this->assertStringContainsString('fd-widget fd-w-6 ', $html);
        $this->assertMatchesRegularExpression('/data-key="stats"[^>]*data-hidden="true"/', $html);
        // The stat cards are not rendered while their widget is hidden.
        $this->assertStringNotContainsString('data-fd-stats', $html);
        $this->assertStringContainsString(__('foundation::foundation.dashboard.hidden_note'), $html);
    }

    public function test_the_layout_endpoint_saves_for_the_signed_in_user_and_reset_restores_the_defaults(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->putJson(route('admin.dashboard.layout.update'), ['items' => [
                ['key' => 'health', 'width' => 6, 'hidden' => false],
                ['key' => 'stats', 'width' => 12, 'hidden' => true],
            ]])
            ->assertOk()
            ->assertJsonPath('layout.0.key', 'health')
            ->assertJsonPath('layout.1.hidden', true);

        $this->assertSame(1, DashboardLayout::query()->where('user_id', $admin->id)->count());

        $this->actingAs($admin)
            ->deleteJson(route('admin.dashboard.layout.reset'))
            ->assertOk()
            ->assertJsonPath('layout.0.key', array_key_first(app(WidgetRegistry::class)->all()));

        $this->assertSame(0, DashboardLayout::query()->count());
    }

    public function test_the_layout_endpoint_validates_its_input_and_needs_a_signed_in_user(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->putJson(route('admin.dashboard.layout.update'), [])->assertUnprocessable();
        $this->actingAs($admin)->putJson(route('admin.dashboard.layout.update'), ['items' => 'nope'])->assertUnprocessable();
        $this->actingAs($admin)->putJson(route('admin.dashboard.layout.update'), ['items' => [['key' => 'health', 'width' => 5]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.width');

    }

    public function test_a_guest_cannot_touch_a_layout(): void
    {
        $this->putJson(route('admin.dashboard.layout.update'), ['items' => []])->assertUnauthorized();
        $this->deleteJson(route('admin.dashboard.layout.reset'))->assertUnauthorized();
    }

    public function test_a_viewer_without_permissions_is_only_offered_the_widgets_they_may_see(): void
    {
        $plain = User::factory()->create();
        $this->actingAs($plain);

        $keys = array_keys(app(WidgetRegistry::class)->all());

        foreach (['user-status', 'sign-in-heatmap', 'activity-by-event', 'activity-feed'] as $gated) {
            $this->assertNotContains($gated, $keys, $gated);
        }

        // A saved layout cannot smuggle one in either.
        $saved = $this->layouts()->save($plain, [['key' => 'user-status', 'width' => 6, 'hidden' => false]]);
        $this->assertNotContains('user-status', $this->keys($saved));
    }
}
