<?php

declare(strict_types=1);

namespace Modules\Otp\Actions;

use Illuminate\Support\Facades\Hash;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Support\OtpThrottle;
use Modules\Otp\Support\PendingRegistration;

/**
 * First step of API sign-up: keep the form and send a code to the phone.
 * The account is created only when the code comes back (CompleteRegistrationAction).
 *
 * Returns the translation key of a throttling refusal, or null when the code
 * was sent.
 *
 * Usage:
 *   app(RequestRegistrationAction::class)->execute('+8801712345678', ['name' => 'Rahim', 'password' => '…']);
 */
final readonly class RequestRegistrationAction
{
    public function __construct(
        private SendOtpAction $sendOtp,
    ) {}

    /**
     * @param  array{name: string, password: string, email?: string|null, role?: string|null}  $data
     */
    public function execute(string $phone, array $data): ?string
    {
        $refusal = OtpThrottle::refusal($phone);

        if ($refusal !== null) {
            return $refusal;
        }

        PendingRegistration::put($phone, [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'role' => $data['role'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $this->sendOtp->execute($phone, ContactType::Phone);

        return null;
    }
}
