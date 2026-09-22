<?php

namespace Modules\Otp\Actions;

use Modules\Otp\Models\OtpWhitelist;

/**
 * Create a new OTP whitelist entry.
 *
 * Enforces uniqueness of (recipient_type, recipient) at the action level,
 * returning null when a duplicate already exists.
 *
 * Usage:
 *   $entry = app(StoreOtpWhitelistAction::class)->execute($validated);
 *   if ($entry === null) { // duplicate }
 */
final readonly class StoreOtpWhitelistAction
{
    /**
     * @param  array{recipient_type: string, recipient: string, fixed_otp: string, is_active?: bool, description?: string|null}  $data
     */
    public function execute(array $data): ?OtpWhitelist
    {
        $exists = OtpWhitelist::where('recipient_type', $data['recipient_type'])
            ->where('recipient', $data['recipient'])
            ->exists();

        if ($exists) {
            return null;
        }

        return OtpWhitelist::create($data);
    }
}
