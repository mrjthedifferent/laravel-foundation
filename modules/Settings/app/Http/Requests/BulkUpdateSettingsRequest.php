<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class BulkUpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['visibility', 'group'])],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:settings,id'],
            // Conditionally required depending on action:
            'visibility' => ['required_if:action,visibility', 'boolean'],
            'group' => ['required_if:action,group', 'string', 'max:255'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'action.required' => 'An action is required.',
            'action.in' => 'Action must be visibility or group.',
            'ids.required' => 'No settings selected.',
            'visibility.required_if' => 'Visibility value is required.',
            'group.required_if' => 'Group name is required.',
        ];
    }
}
