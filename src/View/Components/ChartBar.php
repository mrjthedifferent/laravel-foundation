<?php

namespace Mrj\Foundation\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A ranked list of horizontal bars (label, bar, figure), drawn as plain HTML and CSS so
 * the labels stay crisp and wrap on their own. Fits categories better than a column chart:
 * names of any length stay readable.
 *
 * @internal
 */
final class ChartBar extends Component
{
    /** @var list<array{label: string, value: int, percent: float}> */
    public array $bars = [];

    public bool $hasData;

    public string $summary;

    /**
     * @param  array<string, int>  $series  label => count
     */
    public function __construct(array $series, public string $label = '')
    {
        $peak = $series === [] ? 0 : max($series);
        $this->hasData = $peak > 0;

        foreach ($series as $name => $value) {
            $this->bars[] = [
                'label' => (string) $name,
                'value' => $value,
                'percent' => $peak > 0 ? round($value / $peak * 100, 1) : 0.0,
            ];
        }

        $this->summary = $this->label.': '.collect($this->bars)
            ->map(fn (array $bar): string => $bar['label'].' '.number_format($bar['value']))
            ->implode(', ');
    }

    public function render(): View
    {
        return view('components.chart-bar');
    }
}
