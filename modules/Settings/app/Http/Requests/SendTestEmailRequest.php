<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class SendTestEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'email.required' => __('settings::settings.errors.email_required'),
            'email.email' => __('settings::settings.errors.email_invalid'),
        ];
    }
}
