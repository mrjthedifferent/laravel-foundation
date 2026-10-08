<?php

declare(strict_types=1);

namespace Modules\Otp\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Otp\Http\Requests\Concerns\ValidatesLoginPhone;
use Override;

class RegisterRequest extends FormRequest
{
    use ValidatesLoginPhone;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $roles = (array) config('foundation.registration.roles', []);

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                ...$this->phoneRules(),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (User::query()->wherePhone((string) $value)->exists()) {
                        $fail(__('otp::otp.errors.account_exists', ['type' => 'phone']));
                    }
                },
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::defaults()],
            'role' => $roles === [] ? ['prohibited'] : ['required', 'string', Rule::in($roles)],
            'terms' => config('foundation.registration.require_terms', false) ? ['accepted'] : ['nullable'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'phone.required' => __('otp::otp.validation.phone_required'),
            'phone.regex' => __('otp::otp.validation.phone_invalid'),
        ];
    }
}
