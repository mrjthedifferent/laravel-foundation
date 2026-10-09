<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\UserLoginHistory;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;
use Mrj\Foundation\Services\Dashboard\ChartRegistry;
use Mrj\Foundation\Services\Dashboard\NewUsersChart;
use Mrj\Foundation\Services\Dashboard\StatRegistry;
use Mrj\Foundation\Tests\TestCase;

/**
 * The dashboard's hero: the headline stats every module contributes, the sign-ins
 * chart, and the activity feed.
 */
class DashboardTest extends TestCase
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

    public function test_it_shows_the_headline_stats_a_module_contributes(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('user::user.stat.active_users'))
            ->assertSee(__('user::user.stat.sign_ins_today'))
            ->assertSee('fd-stat-value', false);
    }

    public function test_the_chart_counts_sign_ins_per_day(): void
    {
        Carbon::setTestNow('2026-09-23 12:00:00');
        $admin = $this->admin();

        UserLoginHistory::factory()->count(2)->create([
            'user_id' => $admin->id,
            'logged_in_at' => Carbon::parse('2026-09-23 08:00:00'),
        ]);
        UserLoginHistory::factory()->create([
            'user_id' => $admin->id,
            'logged_in_at' => Carbon::parse('2026-09-21 08:00:00'),
        ]);

        $this->actingAs($admin);
        $chart = app(ChartRegistry::class)->first(14);

        $this->assertNotNull($chart);
        $this->assertCount(14, $chart['series']);
        $this->assertSame(2, $chart['series']['2026-09-23']);
        $this->assertSame(1, $chart['series']['2026-09-21']);
        $this->assertSame(0, $chart['series']['2026-09-22']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('chart-area', false);
    }

    public function test_the_window_comes_from_the_query_string_and_anything_odd_falls_back(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.dashboard', ['days' => 30]))
            ->assertOk()
            ->assertSee('aria-current="page">'.__('foundation::foundation.dashboard.range_days', ['days' => 30]), false);

        foreach ([999, 'abc', -1] as $bogus) {
            $this->actingAs($admin)->get(route('admin.dashboard', ['days' => $bogus]))->assertOk();
        }

        // An array reaches request()->integer() too, and must not blow up.
        $this->actingAs($admin)->get(route('admin.dashboard').'?days[]=1')->assertOk();
    }

    public function test_the_range_picker_offers_four_windows_and_the_old_days_parameter_still_works(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.dashboard', ['range' => 90]))
            ->assertOk()
            ->assertSee(__('foundation::foundation.dashboard.range_days', ['days' => 7]))
            ->assertSee('aria-current="page">'.__('foundation::foundation.dashboard.range_days', ['days' => 90]), false);

        $this->actingAs($admin)->get(route('admin.dashboard', ['days' => 30]))
            ->assertSee('aria-current="page">'.__('foundation::foundation.dashboard.range_days', ['days' => 30]), false);

        foreach ([0, 999, 'abc'] as $bogus) {
            $this->actingAs($admin)->get(route('admin.dashboard', ['range' => $bogus]))
                ->assertSee('aria-current="page">'.__('foundation::foundation.dashboard.range_days', ['days' => 14]), false);
        }
    }

    public function test_comparing_adds_the_previous_period_to_the_chart(): void
    {
        Carbon::setTestNow('2026-09-23 12:00:00');
        $admin = $this->admin();

        UserLoginHistory::factory()->count(3)->create(['user_id' => $admin->id, 'logged_in_at' => Carbon::parse('2026-09-22 08:00:00')]);
        // 14 days before that window: 2026-09-10 .. 2026-09-09 is the window before 09-10..09-23
        UserLoginHistory::factory()->count(2)->create(['user_id' => $admin->id, 'logged_in_at' => Carbon::parse('2026-09-05 08:00:00')]);

        $this->actingAs($admin);
        $plain = app(ChartRegistry::class)->first(14);
        $compared = app(ChartRegistry::class)->first(14, true);

        $this->assertArrayNotHasKey('previous', $plain);
        $this->assertCount(14, $compared['previous']);
        $this->assertSame(2, $compared['previous']['2026-09-05']);
        $this->assertSame(2, array_sum($compared['previous']));
        $this->assertSame($plain['series'], $compared['series']);

        $this->actingAs($admin)->get(route('admin.dashboard', ['compare' => 1]))
            ->assertOk()
            ->assertSee('chart-previous', false)
            ->assertSee(__('foundation::foundation.dashboard.previous_period'))
            ->assertSee('vs previous period');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertDontSee('chart-previous', false);
    }

    public function test_stat_cards_draw_a_sparkline_when_the_stat_has_a_series(): void
    {
        $admin = $this->admin();
        UserLoginHistory::factory()->create(['user_id' => $admin->id, 'logged_in_at' => now()]);
        User::factory()->create(['created_at' => now()->subDays(2)]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('fd-spark', false);

        $stat = collect(app(StatRegistry::class)->all())->firstWhere('label', __('user::user.stat.sign_ins_today'));
        $this->assertCount(14, $stat['series']);
    }

    /**
     * Everything the dashboard caches has to survive a store that refuses objects,
     * so assert the payloads are arrays and scalars at the source.
     */
    public function test_every_cached_dashboard_payload_is_plain_data(): void
    {
        $admin = $this->admin();
        UserLoginHistory::factory()->create(['user_id' => $admin->id, 'logged_in_at' => now()]);
        $this->actingAs($admin);

        $payloads = [
            'stats' => app(StatRegistry::class)->all(),
            'chart' => app(ChartRegistry::class)->first(14),
        ];

        $this->assertSame($payloads, json_decode(json_encode($payloads), true));
        $this->assertNotEmpty($payloads['stats']);
    }

    public function test_the_activity_feed_names_what_changed_and_who_changed_it(): void
    {
        $admin = $this->admin();

        // Written straight to the table: the auditing package attaches no observer
        // while running in the console, so a model update here records nothing.
        DB::table('audits')->insert([
            'user_type' => 'user',
            'user_id' => $admin->id,
            'event' => 'updated',
            'auditable_type' => 'user',
            'auditable_id' => $admin->id,
            'old_values' => '{}',
            'new_values' => '{}',
            'url' => 'http://localhost/admin/users',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('activitylog::activitylog.widget.recent_activity'))
            // "User updated by <name>" — the event label proves the row came from the feed,
            // not from the name showing up in the sidebar.
            ->assertSee(__('activitylog::activitylog.feed.line', [
                'subject' => 'User',
                'event' => __('activitylog::activitylog.feed.event_updated'),
                'actor' => $admin->name,
            ]));
    }

    public function test_a_viewer_without_permissions_gets_no_stats_no_chart_and_no_feed(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('fd-stat-value', false)
            ->assertDontSee('chart-area', false)
            ->assertDontSee('fd-feed', false);
    }

    public function test_the_chart_falls_back_to_new_users_when_sign_ins_are_not_recorded(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        // Stand in for the User module being disabled: its chart cannot answer.
        app()->forgetInstance(ChartRegistry::class);
        $registry = new ChartRegistry;
        $registry->register(NewUsersChart::class);
        app()->instance(ChartRegistry::class, $registry);

        $chart = $registry->first(14);

        $this->assertNotNull($chart);
        $this->assertStringContainsString(__('foundation::foundation.dashboard.chart_new_users', ['days' => 14]), $chart['label']);
        $this->assertSame(DB::table('users')->count(), array_sum($chart['series']));
    }
}
