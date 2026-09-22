<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use App\Models\User;
use Modules\Notification\Notifications\AppNotification;

final readonly class ResetPasswordAction
{
    public function execute(User $user): void
    {
        $password = (string) random_int(10000000, 99999999);

        // The OwenIt Auditable observer automatically records the 'updated' event for the password change
        $user->forceFill(['password' => $password, 'must_change_password' => true])->save();

        $user->notify(new AppNotification(
            title: __('user::user.notifications.password_reset_title'),
            body: __('user::user.notifications.password_reset_body', ['password' => $password]),
            data: ['type' => 'password_reset'],
            channels: ['database', 'mail', 'fcm'],
        ));
    }
}
