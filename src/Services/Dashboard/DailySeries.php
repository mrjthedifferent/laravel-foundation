<?php

namespace Mrj\Foundation\Services\Dashboard;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Counts per calendar day over a window, with the missing days filled in as
 * zeros, so a chart never has to guess what a gap means.
 *
 * Grouped in SQL rather than pulled into PHP: a busy application has orders of
 * magnitude more rows than days. Laravel writes timestamps in the app timezone
 * rather than converting to UTC, so a plain date cast of the stored value is
 * already an app-timezone day and the buckets line up with the today() calls
 * used elsewhere in the package.
 *
 * @internal
 */
final readonly class DailySeries
{
    /** The longest window the dashboard offers, and the cap on anything asked for. */
    public const int MAX_DAYS = 90;

    /**
     * @param  Builder  $query  a base query builder — pass ->toBase() on an Eloquent one
     * @param  string  $column  the timestamp column to bucket by
     * @return array<string, int> ['2026-09-10' => 4, …] oldest day first
     */
    public function count(Builder $query, string $column, int $days): array
    {
        $days = max(1, min($days, self::MAX_DAYS));
        $end = Carbon::today()->addDay();
        $start = $end->copy()->subDays($days);

        /** @var Connection $connection */
        $connection = $query->getConnection();

        $expression = self::expression(
            $connection->getDriverName(),
            $query->getGrammar()->wrap($column),
        );

        $counted = $query
            // A half-open range on the bare column: wrapping it in a cast here
            // would stop the database using an index on it.
            ->where($column, '>=', $start)
            ->where($column, '<', $end)
            // Repeat the expression rather than grouping by the alias: Postgres
            // refuses to group by a select alias.
            ->selectRaw($expression.' as day, count(*) as aggregate')
            ->groupByRaw($expression)
            ->pluck('aggregate', 'day');

        $series = [];

        for ($day = $start->copy(); $day < $end; $day->addDay()) {
            $key = $day->format('Y-m-d');
            $series[$key] = (int) ($counted[$key] ?? 0);
        }

        return $series;
    }

    /**
     * Every driver spells "the day part of this timestamp" differently, and each
     * one is normalised to a Y-m-d string so the keys match what the zero-fill
     * loop produces. Kept static and pure so the drivers CI never runs can still
     * be asserted in a unit test.
     *
     * $column arrives already quoted by the grammar and never comes from input.
     */
    public static function expression(string $driver, string $column): string
    {
        return match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', $column)",
            'pgsql' => "to_char($column, 'YYYY-MM-DD')",
            'sqlsrv' => "convert(varchar(10), $column, 23)",
            default => "date_format($column, '%Y-%m-%d')", // mysql, mariadb
        };
    }
}
