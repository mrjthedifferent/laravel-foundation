<?php

declare(strict_types=1);

namespace Modules\Otp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\Otp\Http\Requests\Concerns\ValidatesLoginPhone;
use Override;

class ResetPasswordWithOtpRequest extends FormRequest
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
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'phone.required' => __('otp::otp.validation.phone_required'),
            'phone.regex' => __('otp::otp.validation.phone_invalid'),
        ];
    }
}
