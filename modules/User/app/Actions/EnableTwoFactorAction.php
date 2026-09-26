<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use App\Models\User;
use Mrj\Foundation\Support\TwoFactorAuthenticator;

/**
 * Starts two-factor setup: a fresh secret and recovery codes. Nothing is
 * enforced until ConfirmTwoFactorAction sees a code from the authenticator;
 * starting again replaces an unconfirmed (or confirmed) secret.
 */
final readonly class EnableTwoFactorAction
{
    public function __construct(private TwoFactorAuthenticator $authenticator) {}

    public function execute(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => $this->authenticator->generateSecret(),
            'two_factor_recovery_codes' => $this->authenticator->generateRecoveryCodes(),
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
