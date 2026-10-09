<?php

namespace Mrj\Foundation\Services\Dashboard;

use Mrj\Foundation\Services\Dashboard\Widgets\PartialWidget;
use Mrj\Foundation\Support\DashboardWidget;
use Nwidart\Modules\Facades\Module;

/**
 * Every widget the enabled modules and the package offer for the dashboard grid.
 *
 * @internal
 */
final class WidgetRegistry
{
    /** @use RegistersComposers<DashboardWidget> */
    use RegistersComposers;

    /**
     * The widgets the signed-in viewer may see, by default order, keyed by widget key.
     *
     * @return array<string, DashboardWidget>
     */
    public function all(): array
    {
        $widgets = array_values(array_filter(
            [...$this->instances(), ...$this->partials()],
            fn (DashboardWidget $widget): bool => $widget->allowed(),
        ));

        usort($widgets, fn (DashboardWidget $a, DashboardWidget $b): int => [$a->order(), $a->key()] <=> [$b->order(), $b->key()]);

        $keyed = [];

        foreach ($widgets as $widget) {
            $keyed[$widget->key()] ??= $widget;
        }

        return $keyed;
    }

    /**
     * The cards enabled modules already ship as a view, so they join the grid without
     * changing: `{module}::partials.dashboard-widget`.
     *
     * @return list<PartialWidget>
     */
    private function partials(): array
    {
        $partials = [];

        foreach (Module::allEnabled() as $module) {
            $view = strtolower($module->getName()).'::partials.dashboard-widget';

            if (view()->exists($view)) {
                $partials[] = new PartialWidget(app(DashboardCache::class), $module->getName(), $view, count($partials));
            }
        }

        return $partials;
    }
}
