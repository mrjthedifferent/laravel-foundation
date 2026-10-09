<?php

namespace Mrj\Foundation\Services\Dashboard\Widgets;

use Illuminate\Contracts\View\View;
use Mrj\Foundation\Services\Dashboard\DashboardContext;
use Mrj\Foundation\Services\Dashboard\QuickActionRegistry;
use Mrj\Foundation\Support\DashboardWidget;
use Override;

/**
 * Shortcuts to the things an administrator does most, from every enabled module.
 *
 * @internal
 */
final class QuickActionsWidget extends DashboardWidget
{
    #[Override]
    public function key(): string
    {
        return 'quick-actions';
    }

    #[Override]
    public function title(): string
    {
        return __('foundation::foundation.dashboard.widget_quick_actions');
    }

    #[Override]
    public function icon(): string
    {
        return 'ph-lightning';
    }

    #[Override]
    public function order(): int
    {
        return 30;
    }

    /**
     * What the viewer may do differs from viewer to viewer, so nothing here is shared.
     */
    #[Override]
    protected function load(DashboardContext $context): ?array
    {
        return $this->data($context);
    }

    #[Override]
    protected function data(DashboardContext $context): ?array
    {
        $actions = app(QuickActionRegistry::class)->all();

        return $actions === [] ? null : ['actions' => $actions];
    }

    #[Override]
    protected function view(array $data, DashboardContext $context): View
    {
        return view('dashboard.widgets.quick-actions', $data);
    }
}
