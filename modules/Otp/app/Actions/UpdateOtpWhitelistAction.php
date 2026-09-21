<?php

namespace Modules\Otp\Actions;

use Modules\Otp\Models\OtpWhitelist;

/**
 * Update an existing OTP whitelist entry.
 *
 * Enforces uniqueness of (recipient_type, recipient) when either field changes,
 * returning false when a conflicting entry already exists.
 *
 * Usage:
 *   $ok = app(UpdateOtpWhitelistAction::class)->execute($whitelist, $validated);
 *   if (!$ok) { // duplicate }
 */
final readonly class UpdateOtpWhitelistAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(OtpWhitelist $whitelist, array $data): bool
    {
        if (isset($data['recipient']) || isset($data['recipient_type'])) {
            $type = $data['recipient_type'] ?? $whitelist->recipient_type->value;
            $recipient = $data['recipient'] ?? $whitelist->recipient;

            $conflict = OtpWhitelist::where('recipient_type', $type)
                ->where('recipient', $recipient)
                ->where('id', '!=', $whitelist->id)
                ->exists();

            if ($conflict) {
                return false;
            }
        }

        $whitelist->update($data);

        return true;
    }
}
