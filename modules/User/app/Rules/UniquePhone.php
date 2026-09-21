<?php

namespace Modules\User\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mrj\Foundation\Support\PhoneNumber;

/**
 * Phone numbers are stored in E.164 form, so uniqueness is checked against the
 * normalized value rather than whatever form was typed.
 */
final readonly class UniquePhone implements ValidationRule
{
    public function __construct(private ?int $ignoreUserId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = PhoneNumber::toE164((string) $value);

        if ($phone === null) {
            return;
        }

        $taken = User::query()
            ->where('phone', $phone)
            ->when($this->ignoreUserId, fn ($query) => $query->whereKeyNot($this->ignoreUserId))
            ->exists();

        if ($taken) {
            $fail('The mobile number has already been taken');
        }
    }
}
