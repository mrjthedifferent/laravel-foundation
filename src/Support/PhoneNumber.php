<?php

namespace Mrj\Foundation\Support;

/**
 * Canonicalises phone numbers to E.164 (e.g. `+8801725700009`) so a number is
 * stored in one consistent form regardless of how it was typed/imported. This
 * keeps login matching, SMS delivery, uniqueness, and masking consistent.
 *
 * The default country/dial code comes from the first value of the
 * `phone_country_codes` setting (falling back to +880):
 *   01725700009      -> +8801725700009   (local trunk 0)
 *   1725700009       -> +8801725700009   (bare national)
 *   8801725700009    -> +8801725700009   (country code, no +)
 *   +8801725700009   -> +8801725700009   (already E.164)
 *   008801725700009  -> +8801725700009   (00 international prefix)
 */
final class PhoneNumber
{
    private const string FALLBACK_DIAL_CODE = '+880';

    /**
     * The default dial code — first entry of the `phone_country_codes` setting,
     * normalised to `+<digits>`. Falls back to +880 when the setting is unset.
     */
    public static function defaultDialCode(): string
    {
        $raw = config('settings.phone_country_codes.value');

        $first = match (true) {
            is_array($raw) => $raw[0] ?? null,
            is_string($raw) && $raw !== '' => explode(',', $raw)[0],
            default => null,
        };

        $digits = $first !== null ? preg_replace('/\D/', '', (string) $first) : '';

        return ($digits === null || $digits === '') ? self::FALLBACK_DIAL_CODE : '+'.$digits;
    }

    /**
     * Normalise a contact that may be an email OR a phone: emails pass through
     * unchanged, phones are canonicalised to E.164. Handy for OTP contacts that
     * can be either. Falls back to the original value if a phone can't be parsed.
     */
    public static function normalizeContact(string $contact): string
    {
        if (str_contains($contact, '@')) {
            return $contact;
        }

        return self::toE164($contact) ?? $contact;
    }

    /**
     * Normalise a raw phone value to E.164. Pass $dialCode to override the
     * settings-derived default (used by tests and non-app contexts).
     */
    public static function toE164(?string $raw, ?string $dialCode = null): ?string
    {
        if ($raw === null) {
            return null;
        }

        $hasPlus = str_starts_with(trim($raw), '+');
        $digits = (string) preg_replace('/\D/', '', $raw);

        if ($digits === '') {
            return null;
        }

        if ($hasPlus) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        $dialCode ??= self::defaultDialCode();
        $dialDigits = ltrim($dialCode, '+');

        if (str_starts_with($digits, '0')) {
            return $dialCode.substr($digits, 1);
        }

        if ($dialDigits !== '' && str_starts_with($digits, $dialDigits)) {
            return '+'.$digits;
        }

        return $dialCode.$digits;
    }
}
