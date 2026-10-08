<?php

declare(strict_types=1);

namespace Modules\Otp\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * A sign-up waiting for its phone to be confirmed by one-time code. Held in
 * the cache (encrypted, password already hashed) for as long as the code is
 * valid, so no account exists until the phone is proven.
 */
final class PendingRegistration
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function put(string $phone, array $data): void
    {
        $minutes = (int) config('settings.otp_expiry_minutes.value', 10);

        Cache::put(self::key($phone), Crypt::encrypt($data), now()->addMinutes($minutes));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function pull(string $phone): ?array
    {
        $payload = Cache::pull(self::key($phone));

        if (! is_string($payload)) {
            return null;
        }

        $data = Crypt::decrypt($payload);

        return is_array($data) ? $data : null;
    }

    public static function has(string $phone): bool
    {
        return Cache::has(self::key($phone));
    }

    private static function key(string $phone): string
    {
        return 'otp:registration:'.hash('sha256', $phone);
    }
}
