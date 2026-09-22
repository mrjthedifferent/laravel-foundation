<?php

namespace Modules\Otp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Otp\Enum\ContactType;
use Mrj\Foundation\Rules\PhoneNumber;
use Override;

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

    #[Override]
    public function messages(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'recipient_type.required' => __('otp::otp.validation.recipient_type_required'),
            'recipient_type.in' => __('otp::otp.validation.recipient_type_in'),
            'recipient.required' => __('otp::otp.validation.recipient_required'),
            'recipient.email' => __('otp::otp.validation.email_invalid'),
            'fixed_otp.required' => __('otp::otp.validation.fixed_otp_required'),
            'fixed_otp.size' => __('otp::otp.validation.fixed_otp_size', ['digits' => $digits]),
            'fixed_otp.regex' => __('otp::otp.validation.fixed_otp_regex'),
        ];
    }

    #[Override]
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
