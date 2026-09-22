<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Mrj\Foundation\Rules\EmailOrPhone;
use Override;

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

    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->filled('id') && ! filter_var($this->input('id'), FILTER_VALIDATE_EMAIL)) {
            $this->merge(['id' => ltrim($this->input('id'), '+')]);
        }
    }

    #[Override]
    public function messages(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'id.required' => __('user::user.errors.otp_id_required'),
            'code.required' => __('user::user.errors.otp_code_required'),
            'code.size' => __('user::user.errors.otp_code_size', ['digits' => $digits]),
        ];
    }
}
