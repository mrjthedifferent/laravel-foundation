<?php

declare(strict_types=1);

namespace Modules\Notification\View\Composers;

use Mrj\Foundation\Support\QuickActionComposer;
use Override;

/**
 * A shortcut to sending a push notification.
 */
final class NotificationQuickActions extends QuickActionComposer
{
    #[Override]
    public function priority(): int
    {
        return 20;
    }

    #[Override]
    public function actions(): array
    {
        return [[
            'label' => __('notification::notification.quick.send'),
            'icon' => 'ph ph-paper-plane-tilt',
            'href' => route('admin.push.notification.create'),
            'permission' => 'Create Push Notification',
        ]];
    }
}
