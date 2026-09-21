<?php

declare(strict_types=1);

namespace Modules\RolePermission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

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

    #[Override]
    public function messages(): array
    {
        return [
            'role_name.required' => 'The role name is required.',
            'role_name.unique' => 'This role name is already taken.',
        ];
    }
}
