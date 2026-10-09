<?php

namespace Mrj\Foundation\Services\Dashboard\Widgets;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Mrj\Foundation\Services\Dashboard\DashboardCache;
use Mrj\Foundation\Services\Dashboard\DashboardContext;
use Mrj\Foundation\Support\DashboardWidget;
use Override;

/**
 * A card a module already provides as a view (`{module}::partials.dashboard-widget`)
 * fed by a view composer that gates it and caches its data. It joins the grid as it is,
 * so those modules need no change to become hideable and movable.
 *
 * @internal
 */
final class PartialWidget extends DashboardWidget
{
    public function __construct(
        DashboardCache $cache,
        private readonly string $module,
        private readonly string $viewName,
        private readonly int $position,
    ) {
        parent::__construct($cache);
    }

    #[Override]
    public function key(): string
    {
        return 'module:'.Str::kebab($this->module);
    }

    #[Override]
    public function title(): string
    {
        return Str::headline($this->module);
    }

    #[Override]
    public function icon(): string
    {
        return 'ph-puzzle-piece';
    }

    #[Override]
    public function order(): int
    {
        return 100 + $this->position;
    }

    /**
     * The module's composer does the permission check and the caching, and hands the
     * view null when the viewer may not see it.
     */
    #[Override]
    protected function load(DashboardContext $context): array
    {
        return [];
    }

    #[Override]
    protected function data(DashboardContext $context): array
    {
        return [];
    }

    #[Override]
    public function render(DashboardContext $context): string
    {
        return trim(view($this->viewName)->render());
    }

    #[Override]
    protected function view(array $data, DashboardContext $context): View
    {
        return view($this->viewName);
    }
}
