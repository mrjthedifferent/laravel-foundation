<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Mrj\Foundation\Rules\EmailOrPhone;

class VerifyLoginOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'id' => ['required', 'string', new EmailOrPhone],
            'code' => ['required', 'string', "size:{$digits}"],
            'role' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('id') && ! filter_var($this->input('id'), FILTER_VALIDATE_EMAIL)) {
            $this->merge(['id' => ltrim($this->input('id'), '+')]);
        }
    }

    public function messages(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'id.required' => 'Email or phone number is required.',
            'code.required' => 'The verification code is required.',
            'code.size' => "The verification code must be {$digits} digits.",
        ];
    }
}
