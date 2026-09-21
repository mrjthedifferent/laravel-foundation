<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class SendTestSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'mobile_no' => ['required', 'string'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'mobile_no.required' => 'A mobile number is required.',
        ];
    }
}
