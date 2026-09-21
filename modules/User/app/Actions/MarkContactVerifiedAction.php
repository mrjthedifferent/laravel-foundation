<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use App\Models\User;
use InvalidArgumentException;

/**
 * Stamps email_verified_at or phone_verified_at on a user, after OTP
 * confirmation or a manual check by an administrator.
 */
final readonly class MarkContactVerifiedAction
{
    /**
     * Mark the given contact field as verified right now.
     *
     * @param  string  $contactType  'email' or 'phone'
     */
    public function execute(User $user, string $contactType): void
    {
        $field = match ($contactType) {
            'email' => 'email_verified_at',
            'phone' => 'phone_verified_at',
            default => throw new InvalidArgumentException("Unsupported contact type: {$contactType}"),
        };

        $user->forceFill([$field => now()])->save();
    }
}
