<?php

declare(strict_types=1);

namespace Modules\Otp\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Set a new password for the account on a phone, proven by a one-time code.
 * Every API token is revoked, so other devices must sign in again.
 *
 * Returns false when the code is wrong or expired, or the number has no account.
 *
 * Usage:
 *   app(ResetPasswordWithOtpAction::class)->execute('+8801712345678', '123456', 'new-password');
 */
final readonly class ResetPasswordWithOtpAction
{
    public function __construct(
        private VerifyOtpAction $verifyOtp,
    ) {}

    public function execute(string $phone, string $code, string $password): bool
    {
        $user = User::query()->wherePhone($phone)->first();

        if ($user === null || ! $this->verifyOtp->execute($phone, $code)) {
            return false;
        }

        DB::transaction(function () use ($user, $password): void {
            $user->forceFill([
                'password' => $password,
                'must_change_password' => false,
                'phone_verified_at' => $user->phone_verified_at ?? now(),
            ])->save();

            $user->tokens()->delete();
        });

        return true;
    }
}
