<?php

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Enum\Gender;
use Modules\User\Rules\UniquePhone;
use Mrj\Foundation\Rules\PhoneNumber;
use Override;

class StoreUserRequest extends FormRequest
{
    /**
     * Authorization is handled by UserPolicy in the controller.
     * This keeps Form Requests focused on validation only.
     */
    public function authorize(): bool
    {
        return true; // Policy checks authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', new PhoneNumber, new UniquePhone],
            'password' => ['required', 'confirmed', 'min:6'],
            'gender' => ['nullable', Rule::in(Gender::values())],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'email.unique' => __('user::user.errors.email_unique'),
            'roles.required' => __('user::user.errors.roles_required'),
            'roles.min' => __('user::user.errors.roles_required'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => ltrim($this->phone, '+')]);
        }

        // Normalize gender to lowercase
        if ($this->has('gender') && $this->gender) {
            $this->merge([
                'gender' => strtolower($this->gender),
            ]);
        }

        // Default is_active to true if not provided
        if (! $this->has('is_active')) {
            $this->merge([
                'is_active' => true,
            ]);
        }
    }
}
