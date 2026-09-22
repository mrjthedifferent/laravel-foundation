<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdateSettingRequest extends FormRequest
{
    /**
     * Authorization is handled by middleware in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    #[Override]
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
            'type' => 'required|string|in:text,textarea,encrypted,file,image,integer,float,boolean,select,multi-select,array,json',
            'description' => 'nullable|string',
            'is_visible' => 'boolean',
            'is_required' => 'boolean',
        ];
    }
}
