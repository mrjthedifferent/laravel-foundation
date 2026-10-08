<?php

declare(strict_types=1);

namespace Modules\Otp\Actions;

use App\Models\User;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Support\OtpThrottle;

/**
 * Send a sign-in code to a phone number for passwordless API login.
 *
 * The outcome never reveals whether an account exists: an unknown number
 * with self-registration turned off is reported as sent, but nothing is
 * sent, so the later verify step simply fails.
 *
 * Returns the translation key of a throttling refusal, or null when the
 * request was accepted.
 *
 * Usage:
 *   $refusal = app(RequestLoginOtpAction::class)->execute('+8801712345678');
 */
final readonly class RequestLoginOtpAction
{
    public function __construct(
        private SendOtpAction $sendOtp,
    ) {}

    public function execute(string $phone): ?string
    {
        $refusal = OtpThrottle::refusal($phone);

        if ($refusal !== null) {
            return $refusal;
        }

        $mayRegister = (bool) config('settings.otp_self_registration_enabled.value', false);

        if ($mayRegister || User::query()->wherePhone($phone)->exists()) {
            $this->sendOtp->execute($phone, ContactType::Phone);
        }

        return null;
    }
}
