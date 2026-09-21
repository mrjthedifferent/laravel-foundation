<?php

namespace Modules\Otp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Otp\Enum\ContactType;
use Mrj\Foundation\Rules\PhoneNumber;

class StoreOtpWhitelistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checks authorization
    }

    public function rules(): array
    {
        $recipientType = $this->input('recipient_type');
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'recipient_type' => ['required', Rule::in(ContactType::values())],
            'recipient' => ['required', $recipientType === ContactType::Email->value ? 'email' : new PhoneNumber],
            'fixed_otp' => ['required', 'string', "size:{$digits}", 'regex:/^[0-9]+$/'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'recipient_type.required' => 'The recipient type is required.',
            'recipient_type.in' => 'The recipient type must be email or phone.',
            'recipient.required' => 'The recipient is required.',
            'recipient.email' => 'Please provide a valid email address.',
            'fixed_otp.required' => 'The fixed OTP is required.',
            'fixed_otp.size' => "The fixed OTP must be exactly {$digits} digits.",
            'fixed_otp.regex' => 'The fixed OTP must contain only digits.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('recipient_type') === ContactType::Phone->value && $this->filled('recipient')) {
            $this->merge(['recipient' => ltrim($this->recipient, '+')]);
        }

        if (! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
