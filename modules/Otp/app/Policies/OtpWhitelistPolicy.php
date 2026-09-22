<?php

namespace Modules\Otp\Policies;

use App\Models\User;
use Modules\Otp\Models\OtpWhitelist;

/**
 * OtpWhitelist Policy
 *
 * Centralized authorization logic for OTP whitelist management.
 * SINGLE SOURCE OF TRUTH for all OTP whitelist permissions.
 */
class OtpWhitelistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('View OTP Whitelist');
    }

    public function view(User $user, OtpWhitelist $otpWhitelist): bool
    {
        return $user->can('View OTP Whitelist');
    }

    public function create(User $user): bool
    {
        return $user->can('Create OTP Whitelist');
    }

    public function update(User $user, OtpWhitelist $otpWhitelist): bool
    {
        return $user->can('Edit OTP Whitelist');
    }

    public function delete(User $user, OtpWhitelist $otpWhitelist): bool
    {
        return $user->can('Delete OTP Whitelist');
    }
}
