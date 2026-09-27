<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTwoFactorSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'required_for_super_admins' => ['nullable', 'boolean'],
            'required_roles' => ['nullable', 'array'],
            'required_roles.*' => ['string', 'exists:roles,name'],
            'issuer' => ['nullable', 'string', 'max:100'],
        ];
    }
}
