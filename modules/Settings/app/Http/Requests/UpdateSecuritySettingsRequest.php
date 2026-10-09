<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'two_factor_enabled' => ['nullable', 'boolean'],
            'two_factor_required_for_super_admins' => ['nullable', 'boolean'],
            'two_factor_required_roles' => ['nullable', 'array'],
            'two_factor_required_roles.*' => ['string', 'exists:roles,name'],
            'two_factor_issuer' => ['nullable', 'string', 'max:100'],
            'password_min_length' => ['required', 'integer', 'between:8,128'],
            'password_mixed_case' => ['nullable', 'boolean'],
            'password_numbers' => ['nullable', 'boolean'],
            'password_symbols' => ['nullable', 'boolean'],
            'password_uncompromised' => ['nullable', 'boolean'],
            'account_deletion_automatic' => ['nullable', 'boolean'],
            'account_deletion_grace_days' => ['nullable', 'integer', 'between:0,365'],
            'login_max_attempts' => ['required', 'integer', 'between:1,100'],
            'session_lifetime' => ['required', 'integer', 'between:5,43200'],
            'api_token_idle_expiration_minutes' => ['required', 'integer', 'between:5,525600'],
        ];
    }
}
