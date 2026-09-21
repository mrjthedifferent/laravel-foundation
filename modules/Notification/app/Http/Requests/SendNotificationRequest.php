<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Requires auth:sanctum middleware
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'channels' => ['required', 'array', 'min:1'],
            // This endpoint delivers a notification to the authenticated user
            // themselves. Cost-bearing channels (mail/sms) are intentionally
            // excluded so an authenticated user cannot drive up email/SMS spend.
            'channels.*' => ['required', 'string', Rule::in(['database', 'fcm'])],
            'data' => ['nullable', 'array'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'title.required' => 'A notification title is required.',
            'body.required' => 'A notification body is required.',
            'channels.required' => 'At least one channel is required.',
            'channels.*.in' => 'Each channel must be one of: database, fcm.',
        ];
    }
}
