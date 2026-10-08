<?php

declare(strict_types=1);

namespace Modules\Otp\Support;

use Modules\Otp\Models\VerificationCode;

/**
 * Per-contact send limits shared by every endpoint that sends a code: one
 * code per 60 seconds, and at most `max_verification_attempts` live codes.
 */
final class OtpThrottle
{
    public const int COOLDOWN_SECONDS = 60;

    /**
     * Returns the translation key of the reason sending is refused, or null
     * when a new code may be sent.
     */
    public static function refusal(string $contact): ?string
    {
        $latest = VerificationCode::active()->contact($contact)->latest()->first();

        if ($latest !== null && $latest->created_at->diffInSeconds(now()) < self::COOLDOWN_SECONDS) {
            return 'otp::otp.errors.wait_before_retry';
        }

        $maxActive = (int) config('settings.max_verification_attempts.value', 5);

        if (VerificationCode::active()->contact($contact)->count() >= $maxActive) {
            return 'otp::otp.errors.max_attempts_reached';
        }

        return null;
    }
}
