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
            'role_name.required' => __('rolepermission::rolepermission.validation.role_name_required'),
            'role_name.unique' => __('rolepermission::rolepermission.validation.role_name_unique'),
        ];
    }
}
