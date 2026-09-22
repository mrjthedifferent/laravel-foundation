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
            'permission_name.unique' => __('rolepermission::rolepermission.validation.permission_name_unique'),
            'module_name.required_without' => __('rolepermission::rolepermission.validation.module_name_required_without'),
            'new_module_name.required_if' => __('rolepermission::rolepermission.validation.new_module_name_required_if'),
        ];
    }
}
