<?php

declare(strict_types=1);

namespace Modules\RolePermission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'role_name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role)],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'role_name.required' => 'The role name is required.',
            'role_name.unique' => 'This role name is already taken.',
        ];
    }
}
