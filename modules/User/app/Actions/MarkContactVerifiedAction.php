<?php

namespace Modules\User\Actions;

use App\Models\User;

/**
 * Stamps email_verified_at on a user after OTP confirmation.
 *
 * Only email carries a verification flag: the users table has no phone column
 * (see User::scopeWherePhone()).
 */
final readonly class MarkContactVerifiedAction
{
    /**
     * Mark the given contact field as verified right now.
     *
     * @param  string  $contactType  'email' (phone verification is retired)
     */
    public function execute(User $user, string $contactType): void
    {
        $field = match ($contactType) {
            'email' => 'email_verified_at',
            default => throw new \InvalidArgumentException("Unsupported contact type: {$contactType}"),
        };

        $user->forceFill([$field => now()])->save();
    }
}
