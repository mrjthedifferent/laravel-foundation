<?php

namespace Mrj\Foundation\Tests\Unit;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Mrj\Foundation\Services\Dashboard\DashboardContext;
use Mrj\Foundation\Services\Dashboard\HourlySeries;
use Mrj\Foundation\Tests\TestCase;
use Mrj\Foundation\View\Components\ChartBar;
use Mrj\Foundation\View\Components\ChartDonut;
use Mrj\Foundation\View\Components\ChartHeatmap;

class DashboardChartsTest extends TestCase
{
    public function test_bars_are_scaled_to_the_biggest_value(): void
    {
        $bar = new ChartBar(['Updated' => 50, 'Created' => 25, 'Deleted' => 0], 'Changes');

        $this->assertTrue($bar->hasData);
        $this->assertSame([100.0, 50.0, 0.0], array_column($bar->bars, 'percent'));
        $this->assertStringContainsString('Updated 50', $bar->summary);
        $this->assertFalse((new ChartBar(['A' => 0]))->hasData);
        $this->assertFalse((new ChartBar([]))->hasData);
    }

    public function test_donut_segments_add_up_to_the_whole_and_start_where_the_last_one_ended(): void
    {
        $donut = new ChartDonut([
            ['label' => 'Active', 'value' => 6, 'tone' => 'success'],
            ['label' => 'Inactive', 'value' => 3],
            ['label' => 'Pending', 'value' => 1],
        ], 'Users');

        $this->assertSame(10, $donut->total);
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($donut->segments, 'percent')), 0.1);
        $this->assertSame([-0.0, -60.0, -90.0], array_map(fn (float $o): float => $o === 0.0 ? -0.0 : $o, array_column($donut->segments, 'offset')));
        $this->assertSame('success', $donut->segments[0]['tone']);
        // A segment that names no tone takes the next one in turn.
        $this->assertSame('success', $donut->segments[1]['tone']);
        $this->assertSame('warning', $donut->segments[2]['tone']);
    }

    public function test_an_empty_donut_has_no_data(): void
    {
        $this->assertFalse((new ChartDonut([['label' => 'A', 'value' => 0]]))->hasData);
        $this->assertFalse((new ChartDonut([]))->hasData);
    }

    public function test_the_heatmap_shades_the_busiest_slot_darkest_and_starts_the_week_on_monday(): void
    {
        $matrix = array_fill(0, 7, array_fill(0, 24, 0));
        $matrix[1][9] = 8;   // Monday 09:00, the peak
        $matrix[1][10] = 4;  // half of the peak
        $matrix[0][23] = 1;  // Sunday 23:00

        $heat = new ChartHeatmap($matrix, 'Sign-ins');

        $this->assertTrue($heat->hasData);
        $this->assertCount(7, $heat->rows);
        $this->assertSame(ChartHeatmap::LEVELS, $heat->rows[0]['cells'][9]['level']);
        $this->assertSame(2, $heat->rows[0]['cells'][10]['level']);
        $this->assertSame(0, $heat->rows[0]['cells'][11]['level']);
        // Sunday is last.
        $this->assertSame(1, $heat->rows[6]['cells'][23]['level']);
        $this->assertSame(Carbon::now()->startOfWeek(Carbon::MONDAY)->isoFormat('ddd'), $heat->rows[0]['label']);
        $this->assertCount(8, $heat->hours);
        $this->assertFalse((new ChartHeatmap(array_fill(0, 7, array_fill(0, 24, 0))))->hasData);
    }

    public function test_hourly_counts_land_in_the_right_weekday_and_hour_cell(): void
    {
        Carbon::setTestNow('2026-09-23 12:00:00'); // a Wednesday

        User::factory()->create(['created_at' => Carbon::parse('2026-09-23 09:15:00')]); // Wed 09
        User::factory()->create(['created_at' => Carbon::parse('2026-09-23 09:45:00')]); // Wed 09
        User::factory()->create(['created_at' => Carbon::parse('2026-09-20 23:05:00')]); // Sun 23
        User::factory()->create(['created_at' => Carbon::parse('2026-01-01 09:00:00')]); // outside the window

        $matrix = app(HourlySeries::class)->matrix(User::query()->toBase(), 'created_at', 14);

        $this->assertCount(7, $matrix);
        $this->assertCount(24, $matrix[3]);
        $this->assertSame(2, $matrix[3][9]);
        $this->assertSame(1, $matrix[0][23]);
        $this->assertSame(3, array_sum(array_map('array_sum', $matrix)));
    }

    public function test_every_driver_has_its_own_weekday_and_hour_expression(): void
    {
        foreach (['sqlite', 'pgsql', 'sqlsrv', 'mysql', 'mariadb'] as $driver) {
            [$weekday, $hour] = HourlySeries::expressions($driver, '"at"');

            $this->assertStringContainsString('"at"', $weekday, $driver);
            $this->assertStringContainsString('"at"', $hour, $driver);
        }

        $this->assertStringContainsString('dayofweek', HourlySeries::expressions('mysql', 'c')[0]);
        $this->assertStringContainsString('strftime', HourlySeries::expressions('sqlite', 'c')[1]);
    }

    public function test_the_context_reads_the_range_and_compare_flag_from_the_request(): void
    {
        $this->assertSame(14, DashboardContext::fromRequest(Request::create('/'))->days);
        $this->assertSame(90, DashboardContext::fromRequest(Request::create('/', 'GET', ['range' => 90]))->days);
        $this->assertSame(30, DashboardContext::fromRequest(Request::create('/', 'GET', ['days' => 30]))->days);
        $this->assertSame(14, DashboardContext::fromRequest(Request::create('/', 'GET', ['range' => 45]))->days);
        $this->assertSame(14, DashboardContext::fromRequest(Request::create('/', 'GET', ['range' => 'abc']))->days);
        $this->assertTrue(DashboardContext::fromRequest(Request::create('/', 'GET', ['compare' => '1']))->compare);
        $this->assertFalse(DashboardContext::fromRequest(Request::create('/', 'GET', ['compare' => '0']))->compare);
    }

    public function test_the_context_builds_links_that_keep_or_drop_its_settings(): void
    {
        $context = new DashboardContext(30, true);

        $this->assertSame(['range' => 30, 'compare' => 1], $context->query());
        $this->assertSame(['range' => 7, 'compare' => 1], $context->query(['range' => 7]));
        $this->assertSame(['range' => 30], $context->query(['compare' => null]));
        $this->assertSame(['range' => 14], (new DashboardContext)->query());
    }
}
