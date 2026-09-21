<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    /**
     * Authorization is handled by middleware in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_visible' => $this->has('is_visible'),
            'is_required' => $this->has('is_required'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group' => 'required|string|max:255',
            'type' => 'required|string|in:text,textarea,file,image,integer,float,boolean,select,multi-select,array,json',
            'description' => 'nullable|string',
            'is_visible' => 'boolean',
            'is_required' => 'boolean',
        ];
    }
}
