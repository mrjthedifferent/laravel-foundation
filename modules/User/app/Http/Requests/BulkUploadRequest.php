<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

/**
 * Form Request for the bulk user upload endpoint.
 *
 * Replaces the inline $request->validate() call in UserController@bulkUpload
 * to be consistent with the rest of the controller pattern.
 */
class BulkUploadRequest extends FormRequest
{
    /**
     * Authorization is handled by UserPolicy in the controller.
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
            'users' => ['required', 'file', 'mimes:xlsx,xls'],
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
            'users.required' => __('user::user.errors.bulk_file_required'),
            'users.file' => __('user::user.errors.bulk_file_invalid'),
            'users.mimes' => __('user::user.errors.bulk_file_mimes'),
        ];
    }
}
