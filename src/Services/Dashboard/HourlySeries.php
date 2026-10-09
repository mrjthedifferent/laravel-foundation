<?php

namespace Mrj\Foundation\Services\Dashboard;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Counts per weekday and hour of the day over a window, for the heatmap. Grouped in SQL,
 * like DailySeries: a busy application has far more rows than the 168 cells it fills.
 * Timestamps are stored in the app timezone, so the weekday and hour are already local.
 *
 * @internal
 */
final readonly class HourlySeries
{
    /**
     * @param  Builder  $query  a base query builder — pass ->toBase() on an Eloquent one
     * @param  string  $column  the timestamp column to bucket by
     * @return list<list<int>> [weekday 0 (Sunday) to 6][hour 0 to 23] => count
     */
    public function matrix(Builder $query, string $column, int $days): array
    {
        $days = max(1, min($days, DailySeries::MAX_DAYS));
        $end = Carbon::today()->addDay();
        $start = $end->copy()->subDays($days);

        /** @var Connection $connection */
        $connection = $query->getConnection();
        $wrapped = $query->getGrammar()->wrap($column);
        [$weekday, $hour] = self::expressions($connection->getDriverName(), $wrapped);

        $rows = $query
            ->where($column, '>=', $start)
            ->where($column, '<', $end)
            ->selectRaw($weekday.' as weekday, '.$hour.' as hour, count(*) as aggregate')
            ->groupByRaw($weekday.', '.$hour)
            ->get();

        $matrix = array_fill(0, 7, array_fill(0, 24, 0));

        foreach ($rows as $row) {
            $day = (int) $row->weekday;
            $slot = (int) $row->hour;

            if (isset($matrix[$day][$slot])) {
                $matrix[$day][$slot] = (int) $row->aggregate;
            }
        }

        return $matrix;
    }

    /**
     * The SQL for "weekday, Sunday = 0" and "hour, 0 to 23" on each driver. Kept static and
     * pure so the drivers CI never runs can still be asserted in a unit test.
     *
     * $column arrives already quoted by the grammar and never comes from input.
     *
     * @return array{0: string, 1: string}
     */
    public static function expressions(string $driver, string $column): array
    {
        return match ($driver) {
            'sqlite' => ["cast(strftime('%w', $column) as integer)", "cast(strftime('%H', $column) as integer)"],
            'pgsql' => ["cast(extract(dow from $column) as integer)", "cast(extract(hour from $column) as integer)"],
            'sqlsrv' => ["((datepart(weekday, $column) + @@datefirst - 1) % 7)", "datepart(hour, $column)"],
            default => ["(dayofweek($column) - 1)", "hour($column)"], // mysql, mariadb
        };
    }
}
