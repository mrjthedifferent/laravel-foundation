<?php

namespace Mrj\Foundation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class EmailOrPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[0-9]{13,14}$/', $value) && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail("The {$attribute} must be a valid phone number or email.");
        }
    }
}
