<?php

namespace Mrj\Foundation\Tests\Unit;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mrj\Foundation\Services\Dashboard\DailySeries;
use Mrj\Foundation\Tests\TestCase;

class DailySeriesTest extends TestCase
{
    private function series(): DailySeries
    {
        return app(DailySeries::class);
    }

    public function test_it_returns_one_bucket_per_day_with_the_gaps_filled(): void
    {
        Carbon::setTestNow('2026-09-23 14:00:00');

        User::factory()->create(['created_at' => Carbon::parse('2026-09-23 09:00:00')]);
        User::factory()->create(['created_at' => Carbon::parse('2026-09-23 23:59:59')]);
        User::factory()->create(['created_at' => Carbon::parse('2026-09-21 12:00:00')]);

        $series = $this->series()->count(User::query()->toBase(), 'created_at', 5);

        $this->assertSame(
            ['2026-09-19', '2026-09-20', '2026-09-21', '2026-09-22', '2026-09-23'],
            array_keys($series),
        );
        $this->assertSame([0, 0, 1, 0, 2], array_values($series));
    }

    public function test_the_window_is_half_open_and_crosses_a_month_boundary(): void
    {
        Carbon::setTestNow('2026-10-02 10:00:00');

        User::factory()->create(['created_at' => Carbon::parse('2026-09-30 23:59:59')]);  // first day
        User::factory()->create(['created_at' => Carbon::parse('2026-10-02 00:00:00')]);  // last day
        User::factory()->create(['created_at' => Carbon::parse('2026-09-29 23:59:59')]);  // just before

        $series = $this->series()->count(User::query()->toBase(), 'created_at', 3);

        $this->assertSame(['2026-09-30' => 1, '2026-10-01' => 0, '2026-10-02' => 1], $series);
    }

    public function test_it_clamps_the_window_it_is_asked_for(): void
    {
        Carbon::setTestNow('2026-09-23 14:00:00');

        $this->assertCount(1, $this->series()->count(User::query()->toBase(), 'created_at', 0));
        $this->assertCount(DailySeries::MAX_DAYS, $this->series()->count(User::query()->toBase(), 'created_at', 5_000));
    }

    public function test_it_counts_rows_rather_than_distinct_days(): void
    {
        Carbon::setTestNow('2026-09-23 14:00:00');

        User::factory()->count(3)->create(['created_at' => Carbon::parse('2026-09-22 08:00:00')]);

        $series = $this->series()->count(User::query()->toBase(), 'created_at', 2);

        $this->assertSame(['2026-09-22' => 3, '2026-09-23' => 0], $series);
    }

    /**
     * CI runs SQLite only, so the expressions for the other drivers are asserted
     * directly — that is the whole reason this is a pure static function.
     */
    public function test_it_knows_how_every_supported_driver_spells_a_day(): void
    {
        $this->assertSame("strftime('%Y-%m-%d', \"created_at\")", DailySeries::expression('sqlite', '"created_at"'));
        $this->assertSame("to_char(\"created_at\", 'YYYY-MM-DD')", DailySeries::expression('pgsql', '"created_at"'));
        $this->assertSame("date_format(`created_at`, '%Y-%m-%d')", DailySeries::expression('mysql', '`created_at`'));
        $this->assertSame("date_format(`created_at`, '%Y-%m-%d')", DailySeries::expression('mariadb', '`created_at`'));
        $this->assertSame('convert(varchar(10), [created_at], 23)', DailySeries::expression('sqlsrv', '[created_at]'));
    }

    public function test_it_leaves_the_query_it_was_given_usable(): void
    {
        Carbon::setTestNow('2026-09-23 14:00:00');
        User::factory()->create();

        $query = DB::table('users');
        $this->series()->count($query, 'created_at', 7);

        // A fresh query still answers, which is what a caller reusing the model expects.
        $this->assertSame(1, User::query()->count());
    }
}
