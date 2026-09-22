<?php

namespace Modules\User\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Enum\Gender;
use Modules\User\Rules\UniquePhone;
use Mrj\Foundation\Rules\PhoneNumber;
use Override;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('id') ?? $this->route('user');
        $userId = $user instanceof User ? $user->id : $user;

        return [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', new PhoneNumber, new UniquePhone((int) $userId)],
            'password' => ['nullable', 'confirmed', 'min:6'],
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

        // Never allow password update via this endpoint
        if ($this->has('password')) {
            $this->merge([
                'password' => null,
            ]);
        }
    }
}
