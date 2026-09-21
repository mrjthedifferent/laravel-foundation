<?php

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
            title: 'Password Changed',
            body: 'Your password has been changed. If you did not change your password, please contact support immediately.',
            channels: ['database', 'fcm'],
        ));
    }
}
