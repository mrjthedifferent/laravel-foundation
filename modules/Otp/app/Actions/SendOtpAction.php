<?php

namespace Modules\Otp\Actions;

use Modules\Otp\Enum\ContactType;
use Modules\Otp\Models\OtpWhitelist;
use Modules\Otp\Models\VerificationCode;
use Modules\Otp\Notifications\SendVerificationCode;

/**
 * Generate an OTP and send it to the contact.
 *
 * Whitelisted contacts skip the notification entirely (dev/testing bypass).
 *
 * Usage:
 *   $code = app(SendOtpAction::class)->execute('user@example.com', ContactType::Email);
 */
final readonly class SendOtpAction
{
    public function __construct(
        private GenerateOtpAction $generateOtp,
    ) {}

    public function execute(string $contact, ContactType $contactType): VerificationCode
    {
        $verificationCode = $this->generateOtp->execute($contact, $contactType);

        $isWhitelisted = OtpWhitelist::findByRecipient($contactType, $contact) !== null;

        if (! $isWhitelisted) {
            $verificationCode->notify(new SendVerificationCode);
        }

        return $verificationCode;
    }
}
