<?php

namespace Mrj\Foundation\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;

/**
 * The dashboard's area chart, drawn as inline SVG from a day => count series.
 * No charting library: the geometry is a handful of sums, and a hand-drawn SVG
 * inherits the theme's accent and border colours for free.
 *
 * @internal
 */
final class ChartArea extends Component
{
    /** The drawing area inside the 640x200 viewBox, leaving room for the axis labels. */
    private const float LEFT = 36.0;

    private const float RIGHT = 630.0;

    private const float TOP = 20.0;

    private const float BOTTOM = 180.0;

    /** @var array<string, int> */
    public array $series;

    /** @var list<array{value: int, y: float}> */
    public array $ticks;

    public string $areaPath;

    public string $linePath;

    public float $lastX;

    public float $lastY;

    public bool $hasData;

    /** Every instance needs its own gradient id, or two charts share one fill. */
    public string $gradientId;

    /** A counter rather than randomness, so the rendered output is stable in tests. */
    private static int $sequence = 0;

    public string $summary;

    /**
     * @param  array<string, int>  $series  ['2026-09-10' => 4, …] oldest day first
     * @param  string  $label  what the series counts, for the accessible summary
     */
    public function __construct(array $series, public string $label = '')
    {
        $this->series = $series;
        $this->gradientId = 'fd-chart-'.(++self::$sequence);

        $values = array_values($series);
        $this->hasData = $values !== [] && max($values) > 0;

        $peak = $values === [] ? 0 : max($values);
        $step = $this->step($peak);
        $top = (int) (ceil(max($peak, 1) / $step) * $step);

        $this->ticks = $this->ticks($top, $step);
        [$this->areaPath, $this->linePath, $this->lastX, $this->lastY] = $this->paths($values, $top);
        $this->summary = $this->summary($series);
    }

    public function render(): View
    {
        return view('components.chart-area');
    }

    /**
     * The gap between gridlines: a 1, 2, 2.5 or 5 times a power of ten, picked so
     * that about four of them cover the data. Rounding the step rather than the
     * top is what keeps the labels themselves round — 400/300/200/100 instead of
     * 25/19/13/6.
     */
    private function step(int $max): float
    {
        $raw = max($max, 1) / 4;
        $magnitude = 10 ** floor(log10($raw));

        foreach ([1, 2, 2.5, 5, 10] as $multiple) {
            $step = $multiple * $magnitude;

            if ($step >= $raw) {
                return $step;
            }
        }

        return 10 * $magnitude;
    }

    /**
     * The gridlines, top first, every one of them on a round number.
     *
     * @return list<array{value: int, y: float}>
     */
    private function ticks(int $top, float $step): array
    {
        $ticks = [];

        for ($value = $top; $value >= $step; $value -= $step) {
            $ticks[] = [
                'value' => (int) round($value),
                'y' => $this->y((int) round($value), $top),
            ];
        }

        return $ticks;
    }

    /**
     * @param  list<int>  $values
     * @return array{0: string, 1: string, 2: float, 3: float}
     */
    private function paths(array $values, int $max): array
    {
        $count = count($values);

        if ($count === 0) {
            return ['', '', self::RIGHT, self::BOTTOM];
        }

        $step = $count > 1 ? (self::RIGHT - self::LEFT) / ($count - 1) : 0.0;
        $points = [];

        foreach ($values as $index => $value) {
            $x = $count > 1 ? self::LEFT + $step * $index : self::RIGHT;
            $points[] = [round($x, 1), $this->y($value, $max)];
        }

        $line = collect($points)
            ->map(fn (array $point, int $index): string => ($index === 0 ? 'M' : 'L').$point[0].' '.$point[1])
            ->implode(' ');

        $last = $points[array_key_last($points)];
        $first = $points[0];
        $area = $line.' L'.$last[0].' '.self::BOTTOM.' L'.$first[0].' '.self::BOTTOM.' Z';

        return [$area, $line, $last[0], $last[1]];
    }

    private function y(int $value, int $max): float
    {
        $ratio = $max > 0 ? $value / $max : 0.0;

        return round(self::BOTTOM - $ratio * (self::BOTTOM - self::TOP), 1);
    }

    /**
     * What a screen reader gets instead of the path: the range and the shape.
     *
     * @param  array<string, int>  $series
     */
    private function summary(array $series): string
    {
        if ($series === []) {
            return $this->label;
        }

        $first = (int) reset($series);
        $last = (int) end($series);
        $firstDay = (string) array_key_first($series);
        $lastDay = (string) array_key_last($series);

        return __('foundation::foundation.dashboard.chart_summary', [
            'label' => $this->label,
            'from' => Carbon::parse($firstDay)->isoFormat('LL'),
            'to' => Carbon::parse($lastDay)->isoFormat('LL'),
            'first' => number_format($first),
            'last' => number_format($last),
            'total' => number_format(array_sum($series)),
        ]);
    }
}
