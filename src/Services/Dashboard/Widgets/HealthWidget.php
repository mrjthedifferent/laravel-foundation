<?php

namespace Mrj\Foundation\Services\Dashboard\Widgets;

use Illuminate\Contracts\View\View;
use Mrj\Foundation\Services\Dashboard\DashboardContext;
use Mrj\Foundation\Services\Dashboard\HealthRegistry;
use Mrj\Foundation\Support\DashboardWidget;
use Mrj\Foundation\Support\HealthCheck;
use Override;

/**
 * "System health": what needs attention first, then what is fine.
 *
 * @internal
 */
final class HealthWidget extends DashboardWidget
{
    #[Override]
    public function key(): string
    {
        return 'health';
    }

    #[Override]
    public function title(): string
    {
        return __('foundation::foundation.dashboard.widget_health');
    }

    #[Override]
    public function icon(): string
    {
        return 'ph-heartbeat';
    }

    #[Override]
    public function order(): int
    {
        return 35;
    }

    /**
     * The registry caches each check and filters them by permission per viewer.
     */
    #[Override]
    protected function load(DashboardContext $context): ?array
    {
        return $this->data($context);
    }

    #[Override]
    protected function data(DashboardContext $context): ?array
    {
        $checks = app(HealthRegistry::class)->all();

        if ($checks === []) {
            return null;
        }

        return [
            'checks' => $checks,
            'problems' => count(array_filter($checks, fn (array $check): bool => $check['status'] !== HealthCheck::OK)),
        ];
    }

    #[Override]
    protected function view(array $data, DashboardContext $context): View
    {
        return view('dashboard.widgets.health', $data);
    }
}
