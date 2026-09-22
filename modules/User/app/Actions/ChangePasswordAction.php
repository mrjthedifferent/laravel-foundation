<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use App\Models\User;
use Modules\Notification\Notifications\AppNotification;

/**
 * Changes the authenticated user's password after verifying the current one.
 *
 * Extracted from Api\UserController so the logic is independently testable
 * and not buried inside the HTTP layer.
 */
final readonly class ChangePasswordAction
{
    public function execute(User $user, string $newPassword): void
    {
        $user->forceFill(['password' => $newPassword, 'must_change_password' => false])->save();

        $user->notify(new AppNotification(
            title: __('user::user.notifications.password_changed_title'),
            body: __('user::user.notifications.password_changed_body'),
            channels: ['database', 'fcm'],
        ));
    }
}
