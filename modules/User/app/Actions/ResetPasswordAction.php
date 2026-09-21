<?php

namespace Modules\User\Actions;

use App\Models\User;
use Modules\Notification\Notifications\AppNotification;

final readonly class ResetPasswordAction
{
    public function execute(User $user): void
    {
        $password = (string) random_int(10000000, 99999999);

        // The OwenIt Auditable observer automatically records the 'updated' event for the password change
        $user->update(['password' => $password]);

        $user->notify(new AppNotification(
            title: 'Password Reset',
            body: "Your password has been reset. Your new password is: {$password}. Please change it after logging in.",
            data: ['type' => 'password_reset'],
            channels: ['database', 'mail', 'fcm'],
        ));
    }
}
