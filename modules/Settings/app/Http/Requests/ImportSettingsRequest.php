<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

    public function messages(): array
    {
        return [
            'settings_file.required' => 'Please select a JSON file to import.',
            'settings_file.mimes' => 'The import file must be a JSON file.',
            'import_mode.required' => 'Please select an import mode.',
            'import_mode.in' => 'Import mode must be either merge or overwrite.',
        ];
    }
}
