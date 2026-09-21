<?php

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Mrj\Foundation\Rules\PhoneNumber;

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
    protected function prepareForValidation(): void
    {
        if ($this->input('contact_type') === 'phone' && $this->filled('contact')) {
            $this->merge(['contact' => ltrim($this->contact, '+')]);
        }
    }

    public function messages(): array
    {
        return [
            'contact_type.required' => 'Contact type is required',
            'contact_type.in' => 'Contact type must be either email or phone',
            'contact.required' => 'Contact information is required',
            'password.required' => 'New password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'password.min' => 'Password must be at least 6 characters',
            'code.required' => 'Verification code is required',
            'code.size' => 'Verification code must be '.(int) config('settings.otp_digit_length.value', 6).' digits',
        ];
    }
}
