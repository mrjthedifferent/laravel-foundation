<?php

namespace Mrj\Foundation\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A tiny trend line for a stat card, drawn as inline SVG from a list of counts.
 * Decorative: the figure beside it carries the meaning, so it is hidden from
 * screen readers.
 *
 * @internal
 */
final class Sparkline extends Component
{
    private const float WIDTH = 100.0;

    private const float HEIGHT = 28.0;

    private const float PAD = 2.0;

    public string $linePath = '';

    public string $areaPath = '';

    public float $lastX = self::WIDTH;

    public float $lastY = self::HEIGHT;

    public bool $hasData = false;

    public string $gradientId;

    private static int $sequence = 0;

    /**
     * @param  list<int>  $series  oldest first
     */
    public function __construct(array $series)
    {
        $this->gradientId = 'fd-spark-'.(++self::$sequence);
        $values = $series;
        $count = count($values);

        // One point is not a trend, and an all-zero series would be a flat line that
        // looks like data.
        $this->hasData = $count > 1 && max($values) > 0;

        if (! $this->hasData) {
            return;
        }

        $peak = max($values);
        $step = (self::WIDTH - self::PAD * 2) / ($count - 1);
        $points = [];

        foreach ($values as $index => $value) {
            $points[] = [
                round(self::PAD + $step * $index, 1),
                round(self::HEIGHT - self::PAD - ($value / $peak) * (self::HEIGHT - self::PAD * 2), 1),
            ];
        }

        $this->linePath = collect($points)
            ->map(fn (array $point, int $index): string => ($index === 0 ? 'M' : 'L').$point[0].' '.$point[1])
            ->implode(' ');

        $last = $points[$count - 1];
        $this->lastX = $last[0];
        $this->lastY = $last[1];
        $this->areaPath = $this->linePath.' L'.$last[0].' '.self::HEIGHT.' L'.$points[0][0].' '.self::HEIGHT.' Z';
    }

    public function render(): View
    {
        return view('components.sparkline');
    }
}
