<?php

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePushNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'user_id' => ['required', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The notification title is required.',
            'body.required' => 'The notification body is required.',
            'user_id.required' => 'Please select a user to notify.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }
}
