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
            'recipient_type' => ['nullable', 'in:specific,all,role'],
            'user_id' => ['required_without:recipient_type', 'required_if:recipient_type,specific', 'nullable', 'exists:users,id'],
            'recipient_role' => ['required_if:recipient_type,role', 'nullable', 'exists:roles,name'],
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
            'user_id.required_if' => __('notification::notification.errors.user_id_required'),
            'recipient_role.required_if' => __('notification::notification.errors.recipient_role_required'),
        ];
    }
}
