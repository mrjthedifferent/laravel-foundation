<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Enum\Gender;
use Override;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Authorization is handled by middleware (auth:sanctum).
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
            'email' => ['nullable', 'email'],
            'email_code' => ['nullable', 'string', 'required_with:email'],
            'name' => ['nullable', 'string', 'max:80'],
            'gender' => ['nullable', Rule::in(Gender::values())],
            // Matches StoreUserRequest/UpdateUserRequest: type and size limited so an
            // arbitrary file (or a file so large it fills the disk) can't reach storage.
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
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
            'email_code.required_with' => __('user::user.errors.email_code_required_with'),
        ];
    }
}
