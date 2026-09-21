<?php

namespace Mrj\Foundation\Support;

/**
 * Canonicalises email addresses so `users.email` and `people.email_official` /
 * `email_personal` are always stored — and looked up — in one consistent form.
 * Postgres string comparison is case-sensitive, so `Email@x.com` written by an
 * import would never match `email@x.com` typed at login. Real-world data has
 * also carried invisible characters pasted from Excel (NBSP U+00A0, word-joiner
 * U+2060) that survive an ASCII trim() and silently break lookups.
 */
final class Email
{
    /**
     * Invisible characters seen in imported data: NBSP, zero-width spaces and
     * joiners, word-joiner, BOM — plus all control characters.
     */
    private const INVISIBLE_CHARS = '/[\x{00A0}\x{200B}-\x{200D}\x{2060}\x{FEFF}\p{Cc}]/u';

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = (string) preg_replace(self::INVISIBLE_CHARS, '', $value);

        return mb_strtolower(trim($clean));
    }
}
