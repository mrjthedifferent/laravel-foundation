<?php

namespace Mrj\Foundation\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A donut for a handful of parts of a whole, drawn as inline SVG. Each segment is a
 * stroke-dasharray on a circle with a circumference of 100, so a part's length is its
 * percentage and no trigonometry is needed.
 *
 * @internal
 */
final class ChartDonut extends Component
{
    /** Token colours, in the order segments take them when they name none. */
    private const array TONES = ['accent', 'success', 'warning', 'danger', 'info', 'muted'];

    public int $total;

    /** @var list<array{label: string, value: int, percent: float, offset: float, tone: string}> */
    public array $segments = [];

    public bool $hasData;

    public string $summary;

    /**
     * @param  list<array{label: string, value: int, tone?: string}>  $parts
     */
    public function __construct(array $parts, public string $label = '')
    {
        $this->total = (int) array_sum(array_column($parts, 'value'));
        $this->hasData = $this->total > 0;

        $offset = 0.0;

        foreach ($parts as $index => $part) {
            $percent = $this->hasData ? round($part['value'] / $this->total * 100, 2) : 0.0;

            $this->segments[] = [
                'label' => $part['label'],
                'value' => $part['value'],
                'percent' => $percent,
                // A dash offset runs against the drawing direction, so it is the negative of the start.
                'offset' => -$offset,
                'tone' => $part['tone'] ?? self::TONES[$index % count(self::TONES)],
            ];

            $offset += $percent;
        }

        $this->summary = $this->label.': '.collect($this->segments)
            ->map(fn (array $segment): string => $segment['label'].' '.number_format($segment['value']))
            ->implode(', ');
    }

    public function render(): View
    {
        return view('components.chart-donut');
    }
}
