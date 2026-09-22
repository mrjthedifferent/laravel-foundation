<?php

namespace Modules\Otp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Models\OtpWhitelist;
use Mrj\Foundation\Rules\PhoneNumber;
use Override;

class UpdateOtpWhitelistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checks authorization
    }

    public function rules(): array
    {
        /** @var OtpWhitelist $whitelist */
        $whitelist = $this->route('otp_whitelist');
        $currentType = $whitelist?->recipient_type instanceof ContactType
            ? $whitelist->recipient_type->value
            : $whitelist?->recipient_type;

        $recipientType = $this->input('recipient_type', $currentType);
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'recipient_type' => ['sometimes', Rule::in(ContactType::values())],
            'recipient' => ['sometimes', $recipientType === ContactType::Email->value ? 'email' : new PhoneNumber],
            'fixed_otp' => ['sometimes', 'string', "size:{$digits}", 'regex:/^[0-9]+$/'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'recipient_type.in' => __('otp::otp.validation.recipient_type_in'),
            'recipient.email' => __('otp::otp.validation.email_invalid'),
            'fixed_otp.size' => __('otp::otp.validation.fixed_otp_size', ['digits' => $digits]),
            'fixed_otp.regex' => __('otp::otp.validation.fixed_otp_regex'),
        ];
    }
}
