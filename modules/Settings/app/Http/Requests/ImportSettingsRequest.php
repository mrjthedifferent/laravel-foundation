<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class ImportSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'settings_file' => ['required', 'file', 'mimes:json'],
            'import_mode' => ['required', Rule::in(['merge', 'overwrite'])],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'settings_file.required' => __('settings::settings.errors.import_file_required'),
            'settings_file.mimes' => __('settings::settings.errors.import_file_mimes'),
            'import_mode.required' => __('settings::settings.errors.import_mode_required'),
            'import_mode.in' => __('settings::settings.errors.import_mode_in'),
        ];
    }
}
