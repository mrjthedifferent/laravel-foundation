<?php

declare(strict_types=1);

namespace Modules\Otp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Otp\Http\Requests\Concerns\ValidatesLoginPhone;
use Override;

class RequestLoginOtpRequest extends FormRequest
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
        return [
            'phone' => $this->phoneRules(),
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
