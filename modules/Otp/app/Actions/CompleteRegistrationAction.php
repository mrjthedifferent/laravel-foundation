<?php

declare(strict_types=1);

namespace Modules\Otp\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Modules\Otp\Support\PendingRegistration;
use Spatie\Permission\Models\Role;

/**
 * Second step of API sign-up: check the code and create the account from the
 * pending form, with the phone already verified.
 *
 * Returns null when the code is wrong or expired, or the pending sign-up has
 * lapsed; the client then starts again.
 *
 * Usage:
 *   $user = app(CompleteRegistrationAction::class)->execute('+8801712345678', '123456');
 */
final readonly class CompleteRegistrationAction
{
    public function __construct(
        private VerifyOtpAction $verifyOtp,
    ) {}

    public function execute(string $phone, string $code): ?User
    {
        if (! PendingRegistration::has($phone) || ! $this->verifyOtp->execute($phone, $code)) {
            return null;
        }

        $data = PendingRegistration::pull($phone);

        if ($data === null || User::query()->wherePhone($phone)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($phone, $data): User {
            $user = User::create([
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                // Already hashed; the model's `hashed` cast leaves it as is.
                'password' => $data['password'],
                'is_active' => true,
            ]);
            $user->forceFill(['phone_verified_at' => now()])->save();

            $role = $data['role'] ?? null;
            $role = is_string($role) && $role !== '' ? $role : (string) config('settings.otp_registration_role.value', '');

            if ($role !== '' && Role::query()->where('name', $role)->exists()) {
                $user->assignRole($role);
            }

            event(new Registered($user));

            return $user;
        });
    }
}
