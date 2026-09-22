<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

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

    #[Override]
    public function messages(): array
    {
        return [
            'title.required' => __('notification::notification.errors.push_title_required'),
            'body.required' => __('notification::notification.errors.push_body_required'),
            'user_id.required' => __('notification::notification.errors.user_id_required'),
            'user_id.exists' => __('notification::notification.errors.user_id_invalid'),
        ];
    }
}
