<?php

declare(strict_types=1);

namespace Modules\User\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Notifications\AppNotification;
use Modules\User\Events\UserRolesChanged;
use Throwable;

/**
 * Notifies a user when their roles (and therefore access) have changed.
 */
class NotifyUserRolesChanged implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserRolesChanged $event): void
    {
        try {
            $event->user->notify(new AppNotification(
                title: 'Your access was updated',
                body: 'Your roles and permissions have been updated by an administrator.',
                type: NotificationType::Info,
                data: ['type' => 'roles_changed'],
                channels: ['database', 'fcm', 'mail'],
            ));
        } catch (Throwable $e) {
            Log::channel('daily_notification')->error('Failed to push roles-changed notice: '.$e->getMessage());
        }
    }
}
