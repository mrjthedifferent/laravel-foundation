<?php

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Enum\Gender;
use Override;
use Spatie\Permission\Models\Role;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $otpLength = (int) config('settings.otp_digit_length.value', 6);

        // Allowed roles come from the DB so they never silently drift
        $allowedRoles = Role::pluck('name')->toArray();

        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'email_code' => ['nullable', 'string', 'required_with:email', "size:{$otpLength}"],
            'role' => ['required', Rule::in($allowedRoles)],
            'name' => ['nullable', 'string', 'max:80'],
            'password' => ['required', 'confirmed', 'min:6'],
            'gender' => ['nullable', Rule::in(Gender::values())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => ltrim($this->phone, '+')]);
        }
    }

    #[Override]
    public function messages(): array
    {
        $otpLength = (int) config('settings.otp_digit_length.value', 6);

        return [
            'email.unique' => 'The email has already been taken',
            'phone.unique' => 'The mobile number has already been taken',
            'phone_code.required_with' => 'Phone verification code is required when phone is provided',
            'email_code.required_with' => 'Email verification code is required when email is provided',
            'phone_code.size' => "Phone verification code must be {$otpLength} digits",
            'email_code.size' => "Email verification code must be {$otpLength} digits",
            'role.in' => 'The selected role is invalid',
        ];
    }
}
