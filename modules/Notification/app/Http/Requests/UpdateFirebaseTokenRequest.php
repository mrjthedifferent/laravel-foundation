<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdateFirebaseTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Requires auth:sanctum middleware
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'device_id' => ['required', 'string'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'token.required' => __('notification::notification.errors.firebase_token_required'),
            'device_id.required' => __('notification::notification.errors.device_id_required'),
        ];
    }
}
