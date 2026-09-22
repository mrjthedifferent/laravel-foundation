<?php

declare(strict_types=1);

namespace Modules\Otp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'contact' => 'required|string',
            'code' => ['required', 'string', 'size:'.$digits],
        ];
    }

    #[Override]
    public function messages(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'contact.required' => __('otp::otp.validation.contact_required'),
            'code.required' => __('otp::otp.validation.code_required'),
            'code.size' => __('otp::otp.validation.code_size', ['digits' => $digits]),
        ];
    }
}
