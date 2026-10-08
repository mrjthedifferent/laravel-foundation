<?php

declare(strict_types=1);

namespace Modules\Otp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Otp\Http\Requests\Concerns\ValidatesLoginPhone;
use Override;

class LoginWithOtpRequest extends FormRequest
{
    use ValidatesLoginPhone;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'phone' => $this->phoneRules(),
            'code' => ['required', 'string', 'size:'.$digits],
            'device_name' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        $digits = (int) config('settings.otp_digit_length.value', 6);

        return [
            'phone.required' => __('otp::otp.validation.phone_required'),
            'phone.regex' => __('otp::otp.validation.phone_invalid'),
            'code.required' => __('otp::otp.validation.code_required'),
            'code.size' => __('otp::otp.validation.code_size', ['digits' => $digits]),
        ];
    }
}
