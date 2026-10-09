<?php

namespace Mrj\Foundation\Services\Dashboard\Widgets;

use Illuminate\Contracts\View\View;
use Mrj\Foundation\Services\Dashboard\DashboardContext;
use Mrj\Foundation\Services\Dashboard\StatRegistry;
use Mrj\Foundation\Support\DashboardWidget;
use Override;

/**
 * The row of headline stat cards every enabled module contributes to.
 *
 * @internal
 */
final class StatsWidget extends DashboardWidget
{
    #[Override]
    public function key(): string
    {
        return 'stats';
    }

    #[Override]
    public function title(): string
    {
        return __('foundation::foundation.dashboard.widget_stats');
    }

    #[Override]
    public function icon(): string
    {
        return 'ph-gauge';
    }

    #[Override]
    public function width(): int
    {
        return 12;
    }

    #[Override]
    public function order(): int
    {
        return 10;
    }

    /**
     * The stats are permission-gated and cached one by one in the registry, so a cache
     * entry of the whole row here would hand one viewer another's cards.
     */
    #[Override]
    protected function load(DashboardContext $context): ?array
    {
        return $this->data($context);
    }

    #[Override]
    protected function data(DashboardContext $context): ?array
    {
        $stats = app(StatRegistry::class)->all();

        return $stats === [] ? null : ['stats' => $stats];
    }

    #[Override]
    protected function view(array $data, DashboardContext $context): View
    {
        return view('dashboard.widgets.stats', $data);
    }
}
