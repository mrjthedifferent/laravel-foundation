<?php

declare(strict_types=1);

namespace Modules\Otp\Support;

use RuntimeException;

/**
 * Keyed hash for one-time codes.
 *
 * An HMAC keyed with the application key is used rather than bcrypt: codes
 * are short-lived and few-digit, so a slow hash adds latency without real
 * protection, while the key means a leaked database alone cannot be used to
 * recover codes. The contact is bound into the hash so a code row cannot be
 * replayed against another contact.
 */
final class OtpHasher
{
    public static function hash(string $contact, string $code): string
    {
        return hash_hmac('sha256', mb_strtolower(trim($contact)).'|'.$code, self::key());
    }

    private static function key(): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new RuntimeException('APP_KEY must be set to hash one-time codes.');
        }

        return str_starts_with($key, 'base64:') ? (string) base64_decode(substr($key, 7), true) : $key;
    }
}
