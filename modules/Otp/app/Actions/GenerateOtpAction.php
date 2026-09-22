<?php

namespace Modules\Otp\Actions;

use Modules\Otp\Enum\ContactType;
use Modules\Otp\Models\OtpWhitelist;
use Modules\Otp\Models\VerificationCode;

/**
 * Generate and persist a new OTP for a given contact.
 *
 * Automatically uses the fixed OTP when the contact is whitelisted
 * (dev/testing bypass), otherwise generates a random N-digit code.
 *
 * Usage:
 *   $code = app(GenerateOtpAction::class)->execute('user@example.com', ContactType::Email);
 */
final readonly class GenerateOtpAction
{
    public function execute(string $contact, ContactType $contactType): VerificationCode
    {
        $whitelist = OtpWhitelist::findByRecipient($contactType, $contact);

        $digits = (int) config('settings.otp_digit_length.value', 6);

        $code = $whitelist
            ? $whitelist->fixed_otp
            : str_pad(random_int(1, (int) str_repeat('9', $digits)), $digits, '0', STR_PAD_LEFT);

        return VerificationCode::create([
            'contact_type' => $contactType,
            'contact' => $contact,
            'code' => $code,
            'expires_at' => now()->addMinutes((int) config('settings.otp_expiry_minutes.value', 10)),
        ]);
    }
}
