<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

/**
 * Lets User module's OTP-based endpoints depend on a contract rather than
 * importing Modules\Otp\Actions\VerifyOtpAction directly, since Otp is an
 * optional module. Bound to a NullOtpVerifier by default (always fails
 * verification, since there is nothing to verify against without the
 * module); the Otp module rebinds it to the real VerifyOtpAction.
 *
 * @api
 */
interface OtpVerifier
{
    public function execute(string $contact, string $code, bool $isCheck = false): bool;
}
