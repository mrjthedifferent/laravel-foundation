<?php

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Mrj\Foundation\Rules\PhoneNumber;
use Override;

class ResetPasswordRequest extends FormRequest
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
        $contactType = $this->input('contact_type');

        $contactRule = match ($contactType) {
            'email' => 'email',
            'phone' => new PhoneNumber,
            default => 'string',
        };

        return [
            'contact_type' => ['required', 'in:email,phone'],
            'contact' => ['required', $contactRule],
            'password' => ['required', 'confirmed', 'min:6'],
            'code' => ['required', 'string', 'size:'.(int) config('settings.otp_digit_length.value', 6)],
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
        if ($this->input('contact_type') === 'phone' && $this->filled('contact')) {
            $this->merge(['contact' => ltrim($this->contact, '+')]);
        }
    }

    #[Override]
    public function messages(): array
    {
        return [
            'contact_type.required' => __('user::user.errors.contact_type_required'),
            'contact_type.in' => __('user::user.errors.contact_type_invalid'),
            'contact.required' => __('user::user.errors.contact_required'),
            'password.required' => __('user::user.errors.new_password_required'),
            'password.confirmed' => __('user::user.errors.password_confirmation_mismatch'),
            'password.min' => __('user::user.errors.password_min'),
            'code.required' => __('user::user.errors.verification_code_required'),
            'code.size' => __('user::user.errors.verification_code_size', ['digits' => (int) config('settings.otp_digit_length.value', 6)]),
        ];
    }
}
