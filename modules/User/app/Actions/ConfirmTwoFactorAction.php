<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use App\Models\User;
use Modules\Notification\Notifications\AppNotification;
use Mrj\Foundation\Support\TwoFactorAuthenticator;

/**
 * Turns two-factor authentication on once the user proves their
 * authenticator produces the right codes. False when the code is wrong.
 */
final readonly class ConfirmTwoFactorAction
{
    public function __construct(private TwoFactorAuthenticator $authenticator) {}

    public function execute(User $user, string $code): bool
    {
        if ($user->two_factor_secret === null || ! $this->authenticator->verify($user, $code)) {
            return false;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $user->notify(new AppNotification(
            title: __('user::user.notifications.two_factor_enabled_title'),
            body: __('user::user.notifications.two_factor_enabled_body'),
            channels: ['database'],
        ));

        return true;
    }
}
