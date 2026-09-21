<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class BulkDeleteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:settings,id'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'ids.required' => 'No settings selected.',
        ];
    }
}
