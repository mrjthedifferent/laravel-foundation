<?php

namespace Mrj\Foundation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[1-9][0-9]{6,14}$/', $value)) {
            $fail("The {$attribute} must be a valid international phone number without the + prefix (e.g., 8801712345678).");

            return;
        }

        $allowedCodes = config('settings.phone_country_codes.value', []);

        if (! empty($allowedCodes)) {
            $matched = collect($allowedCodes)->contains(fn ($code) => str_starts_with($value, ltrim($code, '+')));

            if (! $matched) {
                $fail("The {$attribute} must use an allowed country code (".implode(', ', $allowedCodes).').');
            }
        }
    }
}
