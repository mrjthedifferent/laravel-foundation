<?php

namespace Modules\Otp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Otp\Enum\ContactType;
use Mrj\Foundation\Rules\PhoneNumber;
use Override;

class SendVerificationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $contactType = $this->input('contact_type');

        return [
            'contact_type' => ['required', Rule::in(ContactType::values())],
            'contact' => ['required', $contactType === ContactType::Email->value ? 'email' : new PhoneNumber],
            'is_registration' => ['nullable', 'boolean'],
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        // Strip a leading '+' so the digits-only PhoneNumber rule passes; the
        // controller canonicalises to E.164 (via PhoneNumber::toE164) before use.
        if ($this->input('contact_type') === ContactType::Phone->value && $this->filled('contact')) {
            $this->merge(['contact' => ltrim($this->contact, '+')]);
        }
    }

    #[Override]
    public function messages(): array
    {
        return [
            'contact_type.required' => __('otp::otp.validation.contact_type_required'),
            'contact_type.in' => __('otp::otp.validation.contact_type_in'),
            'contact.required' => __('otp::otp.validation.contact_required'),
            'contact.email' => __('otp::otp.validation.email_invalid'),
        ];
    }
}
