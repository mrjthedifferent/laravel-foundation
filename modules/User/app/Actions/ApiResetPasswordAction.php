<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Modules\Notification\Notifications\AppNotification;

/**
 * Resets a user's password via OTP-verified contact (API flow).
 *
 * OTP verification must be performed by the caller (VerifyOtpAction) before
 * invoking this action. This action only handles the actual password change,
 * verification stamping, and downstream notifications / events.
 */
final readonly class ApiResetPasswordAction
{
    /**
     * @param  User  $user  The user whose password is being reset.
     * @param  string  $contactType  'email' or 'phone'
     * @param  string  $newPassword  Already-hashed (via 'hashed' cast) plain-text password.
     */
    public function execute(User $user, string $contactType, string $newPassword): void
    {
        $user->update(['password' => $newPassword]);

        // Stamp the contact as verified since the OTP was already validated by the caller.
        if (in_array($contactType, ['email', 'phone'], true)) {
            $user->forceFill([$contactType.'_verified_at' => now()])->save();
        }

        event(new PasswordReset($user));

        $user->notify(new AppNotification(
            title: 'Password Changed',
            body: 'Your password has been changed. If you did not change your password, please contact support immediately.',
            channels: ['database', 'fcm'],
        ));
    }
}
