<?php

declare(strict_types=1);

namespace Modules\Otp\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Exchange a phone number and its one-time code for a user, creating the
 * account on first sign-in when self-registration is enabled.
 *
 * Returns null when the code is wrong, expired or used, or when the number
 * has no account and self-registration is off. The phone is stamped as
 * verified, since possession of the code proves it.
 *
 * Usage:
 *   $result = app(LoginWithOtpAction::class)->execute('+8801712345678', '123456', 'Rahim');
 *   // ['user' => User, 'is_new_user' => bool] or null
 */
final readonly class LoginWithOtpAction
{
    public function __construct(
        private VerifyOtpAction $verifyOtp,
    ) {}

    /**
     * @return array{user: User, is_new_user: bool}|null
     */
    public function execute(string $phone, string $code, ?string $name = null): ?array
    {
        if (! $this->verifyOtp->execute($phone, $code)) {
            return null;
        }

        $user = User::query()->wherePhone($phone)->first();

        if ($user !== null) {
            if ($user->phone_verified_at === null) {
                $user->forceFill(['phone_verified_at' => now()])->save();
            }

            return ['user' => $user, 'is_new_user' => false];
        }

        if (! (bool) config('settings.otp_self_registration_enabled.value', false)) {
            return null;
        }

        return ['user' => $this->register($phone, $name), 'is_new_user' => true];
    }

    private function register(string $phone, ?string $name): User
    {
        return DB::transaction(function () use ($phone, $name): User {
            $user = User::create([
                'phone' => $phone,
                'name' => $name !== null && trim($name) !== '' ? trim($name) : null,
                // Never used to sign in; the account can set a real password later.
                'password' => Str::random(64),
                'is_active' => true,
            ]);
            $user->forceFill(['phone_verified_at' => now()])->save();

            $roleName = (string) config('settings.otp_registration_role.value', '');

            if ($roleName !== '' && Role::query()->where('name', $roleName)->exists()) {
                $user->assignRole($roleName);
            }

            event(new Registered($user));

            return $user;
        });
    }
}
