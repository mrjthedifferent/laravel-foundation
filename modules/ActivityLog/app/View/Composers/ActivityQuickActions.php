<?php

declare(strict_types=1);

namespace Modules\ActivityLog\View\Composers;

use Mrj\Foundation\Support\QuickActionComposer;
use Override;

/**
 * A shortcut to the activity log.
 */
final class ActivityQuickActions extends QuickActionComposer
{
    #[Override]
    public function priority(): int
    {
        return 30;
    }

    #[Override]
    public function actions(): array
    {
        return [[
            'label' => __('activitylog::activitylog.quick.activity_logs'),
            'icon' => 'ph-activity',
            'href' => route('admin.activity-logs.index'),
            'permission' => 'View Activity Log',
        ]];
    }
}
