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
            'action.required' => __('settings::settings.errors.bulk_action_required'),
            'action.in' => __('settings::settings.errors.bulk_action_in'),
            'ids.required' => __('settings::settings.errors.bulk_ids_required'),
            'visibility.required_if' => __('settings::settings.errors.bulk_visibility_required'),
            'group.required_if' => __('settings::settings.errors.bulk_group_required'),
        ];
    }
}
