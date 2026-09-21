<?php

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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

    public function messages(): array
    {
        return [
            'token.required' => 'A Firebase token is required.',
            'device_id.required' => 'A device ID is required.',
        ];
    }
}
