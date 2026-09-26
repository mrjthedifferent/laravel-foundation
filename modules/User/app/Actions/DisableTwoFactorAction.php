<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use App\Models\User;
use Modules\Notification\Notifications\AppNotification;

/**
 * Turns two-factor authentication off: by the user from their profile, or by
 * an administrator for a user who lost their authenticator and recovery codes.
 * A user who must use 2FA is sent to set it up again on their next request.
 */
final readonly class DisableTwoFactorAction
{
    public function execute(User $user): void
    {
        $wasEnabled = $user->hasTwoFactorEnabled();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        if ($wasEnabled) {
            $user->notify(new AppNotification(
                title: __('user::user.notifications.two_factor_disabled_title'),
                body: __('user::user.notifications.two_factor_disabled_body'),
                channels: ['database'],
            ));
        }
    }
}
