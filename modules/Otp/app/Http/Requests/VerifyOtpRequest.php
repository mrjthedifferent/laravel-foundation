<?php

namespace Modules\Otp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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

    public function messages(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'contact.required' => 'The contact (email or phone) is required.',
            'code.required' => 'The verification code is required.',
            'code.size' => "The verification code must be {$digits} digits.",
        ];
    }
}
