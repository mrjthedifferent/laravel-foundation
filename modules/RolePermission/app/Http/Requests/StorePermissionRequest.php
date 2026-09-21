<?php

declare(strict_types=1);

namespace Modules\RolePermission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'module_name' => ['required_without:new_module_name', 'string', 'max:255'],
            'new_module_name' => ['required_if:module_name,new', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'permission_name.unique' => 'This permission name already exists.',
            'module_name.required_without' => 'Please select a module or create a new one.',
            'new_module_name.required_if' => 'Please enter a name for the new module.',
        ];
    }
}
