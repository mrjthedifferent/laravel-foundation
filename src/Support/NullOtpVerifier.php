<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

use Mrj\Foundation\Contracts\OtpVerifier;
use Override;

/**
 * Default binding for OtpVerifier when the Otp module isn't installed.
 * There is no verification code to check against, so every attempt fails.
 */
final class NullOtpVerifier implements OtpVerifier
{
    #[Override]
    public function execute(string $contact, string $code, bool $isCheck = false): bool
    {
        return false;
    }
}
