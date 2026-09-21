<?php

namespace Modules\RolePermission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'role_name.required' => 'The role name is required.',
            'role_name.unique' => 'This role name is already taken.',
        ];
    }
}
