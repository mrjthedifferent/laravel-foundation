<?php

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Enum\AccountAction;
use Override;

class ManageUserAccountRequest extends FormRequest
{
    /**
     * Authorization is handled by UserPolicy (manageAccount) in the controller.
     * Policy enforces 'Delete User' permission and prevents self-management.
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
            'action' => ['required', Rule::enum(AccountAction::class)],
            'user_id' => ['nullable', 'exists:users,id'],
            'password' => [$this->isApiRequest() ? 'required' : 'nullable', 'string'],
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
            'action.required' => __('user::user.errors.action_required'),
            'action.enum' => __('user::user.errors.action_invalid'),
            'user_id.exists' => __('user::user.errors.user_not_found'),
        ];
    }

    /**
     * Check if this is an API request.
     */
    protected function isApiRequest(): bool
    {
        return $this->is('api/*');
    }
}
