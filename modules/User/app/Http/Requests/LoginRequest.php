<?php

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Mrj\Foundation\Rules\EmailOrPhone;
use Override;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', new EmailOrPhone],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->filled('id') && ! filter_var($this->id, FILTER_VALIDATE_EMAIL)) {
            $this->merge(['id' => ltrim($this->id, '+')]);
        }
    }

    #[Override]
    public function messages(): array
    {
        return [
            'id.required' => __('user::user.errors.login_id_required'),
            'password.required' => __('user::user.errors.login_password_required'),
        ];
    }
}
