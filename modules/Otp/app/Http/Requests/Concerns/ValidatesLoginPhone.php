<?php

declare(strict_types=1);

namespace Modules\Otp\Http\Requests\Concerns;

use Closure;
use Mrj\Foundation\Support\PhoneNumber;

/**
 * Accepts a phone number in any typed form (local `01712…`, `880…`,
 * `+880…`), normalises it to E.164 before validation, and checks it against
 * the allowed country codes.
 */
trait ValidatesLoginPhone
{
    protected function prepareForValidation(): void
    {
        $raw = $this->input('phone');

        if (is_string($raw) && $raw !== '') {
            $this->merge(['phone' => PhoneNumber::toE164($raw) ?? $raw]);
        }
    }

    /**
     * @return array<int, mixed>
     */
    protected function phoneRules(): array
    {
        return [
            'required',
            'string',
            'regex:/^\+[1-9]\d{6,14}$/',
            function (string $attribute, mixed $value, Closure $fail): void {
                $allowed = config('settings.phone_country_codes.value', []);
                $allowed = is_string($allowed) ? array_filter(explode(',', $allowed)) : (array) $allowed;

                if ($allowed === []) {
                    return;
                }

                $matched = collect($allowed)->contains(
                    fn ($code): bool => str_starts_with((string) $value, '+'.ltrim(trim((string) $code), '+'))
                );

                if (! $matched) {
                    $fail(__('otp::otp.validation.phone_country_not_allowed'));
                }
            },
        ];
    }
}
