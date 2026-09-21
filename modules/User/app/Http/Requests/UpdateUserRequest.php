<?php

namespace Modules\User\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Enum\Gender;

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
    public function messages(): array
    {
        return [
            'email.unique' => 'The email has already been taken',
            'phone.unique' => 'The mobile number has already been taken',
            'roles.required' => 'At least one role must be assigned',
            'roles.min' => 'At least one role must be assigned',
        ];
    }

    /**
     * Prepare the data for validation.
     */
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

    /**
     * Check if roles are being changed.
     */
    protected function rolesAreChanging(): bool
    {
        $userId = $this->route('id') ?? $this->route('user');
        $user = User::find($userId);

        if (! $user) {
            return false;
        }

        $currentRoles = $user->roles->pluck('id')->toArray();
        $newRoles = $this->input('roles', []);

        return array_diff($currentRoles, $newRoles) !== []
            || array_diff($newRoles, $currentRoles) !== [];
    }
}
