<?php

namespace Modules\Otp\Actions;

use Modules\Otp\Models\VerificationCode;

/**
 * Verify an OTP code for a contact and mark it as used on success.
 *
 * Returns true when the code is valid and active, false otherwise.
 * On success the code is immediately consumed (marked as verified)
 * so it cannot be reused.
 *
 * Usage:
 *   $ok = app(VerifyOtpAction::class)->execute('user@example.com', '123456');
 */
final readonly class VerifyOtpAction
{
    public function execute(string $contact, string $code, bool $isCheck = false): bool
    {
        // Look up the latest active code for this contact (independent of the
        // submitted value) so that wrong guesses can be counted against it and
        // the code invalidated after too many failed attempts.
        $verificationCode = VerificationCode::active()
            ->contact($contact)
            ->latest()
            ->first();

        if ($verificationCode === null) {
            return false;
        }

        if (! hash_equals((string) $verificationCode->code, $code)) {
            $verificationCode->registerFailedAttempt();

            return false;
        }

        if (! $isCheck) {
            $verificationCode->markAsVerified();
        }

        return true;
    }
}
