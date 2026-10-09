<?php

namespace Mrj\Foundation\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;

/**
 * Activity by weekday and hour, as a grid of cells whose tint says how busy that slot was.
 * Plain HTML and CSS: 168 cells are cheap, and each carries its own tooltip and screen
 * reader label.
 *
 * @internal
 */
final class ChartHeatmap extends Component
{
    /** How many tint steps above empty. */
    public const int LEVELS = 4;

    public bool $hasData;

    /** @var list<array{label: string, cells: list<array{level: int, title: string}>}> */
    public array $rows = [];

    /** @var list<string> every third hour, "00".."21", for the header */
    public array $hours = [];

    public string $summary;

    /**
     * @param  list<list<int>>  $matrix  [weekday 0 (Sunday) to 6][hour 0 to 23] => count
     */
    public function __construct(array $matrix, public string $label = '')
    {
        $peak = 0;
        $total = 0;

        foreach ($matrix as $day) {
            $peak = max($peak, max($day));
            $total += array_sum($day);
        }

        $this->hasData = $peak > 0;

        // Monday first, the way a working week reads.
        foreach ([1, 2, 3, 4, 5, 6, 0] as $weekday) {
            $name = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays($weekday)->isoFormat('ddd');

            $this->rows[] = [
                'label' => $name,
                'cells' => array_map(fn (int $count, int $hour): array => [
                    'level' => $this->level($count, $peak),
                    'title' => $name.' '.str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00, '.number_format($count),
                ], $matrix[$weekday], array_keys($matrix[$weekday])),
            ];
        }

        $this->hours = array_map(fn (int $hour): string => str_pad((string) $hour, 2, '0', STR_PAD_LEFT), range(0, 21, 3));
        $this->summary = $this->label.': '.number_format($total);
    }

    /**
     * 0 for an empty slot, then 1 to LEVELS in even steps of the busiest slot.
     */
    private function level(int $count, int $peak): int
    {
        if ($count <= 0 || $peak <= 0) {
            return 0;
        }

        return max(1, (int) ceil($count / $peak * self::LEVELS));
    }

    public function render(): View
    {
        return view('components.chart-heatmap');
    }
}
