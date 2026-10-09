<?php

namespace Modules\ActivityLog\View\Widgets;

use Illuminate\Contracts\View\View;
use Mrj\Foundation\Services\Dashboard\DashboardContext;
use Mrj\Foundation\Support\DashboardWidget;
use Override;

/**
 * The "Recent activity" card. Its view composer gates and caches it and hands the view
 * null for a viewer who may not see it, which renders nothing.
 *
 * @internal
 */
final class ActivityFeedWidget extends DashboardWidget
{
    private const string VIEW = 'activitylog::partials.dashboard-feed';

    #[Override]
    public function key(): string
    {
        return 'activity-feed';
    }

    #[Override]
    public function title(): string
    {
        return __('foundation::foundation.dashboard.widget_activity');
    }

    #[Override]
    public function icon(): string
    {
        return 'ph-clock-counter-clockwise';
    }

    #[Override]
    public function order(): int
    {
        return 25;
    }

    #[Override]
    public function permissions(): array
    {
        return ['View Activity Log'];
    }

    #[Override]
    public function render(DashboardContext $context): string
    {
        return $this->allowed() ? trim(view(self::VIEW)->render()) : '';
    }

    #[Override]
    protected function data(DashboardContext $context): array
    {
        return [];
    }

    #[Override]
    protected function view(array $data, DashboardContext $context): View
    {
        return view(self::VIEW);
    }
}
