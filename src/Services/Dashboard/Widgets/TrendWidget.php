<?php

namespace Mrj\Foundation\Services\Dashboard\Widgets;

use Illuminate\Contracts\View\View;
use Mrj\Foundation\Services\Dashboard\ChartRegistry;
use Mrj\Foundation\Services\Dashboard\DashboardContext;
use Mrj\Foundation\Support\DashboardWidget;
use Override;

/**
 * The area chart: the first chart the viewer may see, over the chosen range and, when
 * asked, against the period before it.
 *
 * @internal
 */
final class TrendWidget extends DashboardWidget
{
    #[Override]
    public function key(): string
    {
        return 'trend';
    }

    #[Override]
    public function title(): string
    {
        return __('foundation::foundation.dashboard.widget_trend');
    }

    #[Override]
    public function icon(): string
    {
        return 'ph-chart-line-up';
    }

    #[Override]
    public function width(): int
    {
        return 8;
    }

    #[Override]
    public function order(): int
    {
        return 20;
    }

    /**
     * Charts are permission-gated and cached by the registry's composers.
     */
    #[Override]
    protected function load(DashboardContext $context): ?array
    {
        return $this->data($context);
    }

    #[Override]
    protected function data(DashboardContext $context): ?array
    {
        $chart = app(ChartRegistry::class)->first($context->days, $context->compare);

        if ($chart === null) {
            return null;
        }

        $total = array_sum($chart['series']);
        $before = isset($chart['previous']) ? array_sum($chart['previous']) : null;

        return [
            'chart' => $chart,
            'total' => $total,
            // Growth from nothing has no percentage; a flat period has none to show either.
            'delta' => $before === null || $before === 0 ? null : round(($total - $before) / $before * 100, 1),
        ];
    }

    #[Override]
    protected function view(array $data, DashboardContext $context): View
    {
        return view('dashboard.widgets.trend', $data);
    }
}
