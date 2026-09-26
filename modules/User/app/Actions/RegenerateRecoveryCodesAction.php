<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use App\Models\User;
use Mrj\Foundation\Support\TwoFactorAuthenticator;

/**
 * A new set of recovery codes; the old ones stop working.
 */
final readonly class RegenerateRecoveryCodesAction
{
    public function __construct(private TwoFactorAuthenticator $authenticator) {}

    /**
     * @return list<string>
     */
    public function execute(User $user): array
    {
        $codes = $this->authenticator->generateRecoveryCodes();

        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return $codes;
    }
}
