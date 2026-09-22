<?php

namespace Mrj\Foundation\Tests\Unit;

use Mrj\Foundation\Tests\TestCase;
use Mrj\Foundation\View\Components\ChartArea;

class ChartAreaTest extends TestCase
{
    /**
     * @param  list<int>  $values
     * @return array<string, int>
     */
    private function series(array $values): array
    {
        $series = [];

        foreach ($values as $index => $value) {
            $series['2026-09-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)] = $value;
        }

        return $series;
    }

    /**
     * @return list<int>
     */
    private function tickValues(ChartArea $chart): array
    {
        return array_map(fn (array $tick): int => $tick['value'], $chart->ticks);
    }

    public function test_the_gridlines_land_on_round_numbers(): void
    {
        $this->assertSame([400, 300, 200, 100], $this->tickValues(new ChartArea($this->series([210, 342]))));
        $this->assertSame([30, 20, 10], $this->tickValues(new ChartArea($this->series([3, 21]))));
        $this->assertSame([8, 6, 4, 2], $this->tickValues(new ChartArea($this->series([7]))));
        $this->assertSame([1500, 1000, 500], $this->tickValues(new ChartArea($this->series([1284]))));
    }

    public function test_the_line_spans_the_plot_and_the_marker_sits_on_its_last_point(): void
    {
        $chart = new ChartArea($this->series([1, 2, 3, 4]));

        $this->assertStringStartsWith('M36 ', $chart->linePath);
        $this->assertSame(630.0, $chart->lastX);
        $this->assertStringEndsWith('Z', $chart->areaPath);
        $this->assertStringContainsString('L630 '.$chart->lastY, $chart->linePath);
    }

    public function test_two_charts_on_one_page_do_not_share_a_gradient(): void
    {
        $first = new ChartArea($this->series([1, 2]));
        $second = new ChartArea($this->series([3, 4]));

        $this->assertNotSame($first->gradientId, $second->gradientId);
    }

    public function test_a_series_with_nothing_in_it_reports_no_data_instead_of_dividing_by_zero(): void
    {
        $empty = new ChartArea([]);
        $zeros = new ChartArea($this->series([0, 0, 0]));

        $this->assertFalse($empty->hasData);
        $this->assertFalse($zeros->hasData);
        // Still drawn, so a caller that renders it anyway gets a flat baseline.
        $this->assertSame([180.0, 180.0, 180.0], array_map(
            fn (string $point): float => (float) explode(' ', $point)[1],
            explode(' L', str_replace('M', '', $zeros->linePath)),
        ));
    }

    public function test_a_flat_series_is_drawn_level(): void
    {
        $chart = new ChartArea($this->series([5, 5, 5]));

        $this->assertTrue($chart->hasData);
        $this->assertSame($chart->lastY, (float) explode(' ', str_replace('M', '', $chart->linePath))[1]);
    }

    public function test_it_summarises_the_series_for_a_screen_reader(): void
    {
        $chart = new ChartArea($this->series([10, 40]), 'Sign-ins');

        $this->assertStringContainsString('Sign-ins', $chart->summary);
        $this->assertStringContainsString('10', $chart->summary);
        $this->assertStringContainsString('40', $chart->summary);
    }
}
