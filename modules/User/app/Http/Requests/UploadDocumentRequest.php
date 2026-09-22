<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Enum\DocumentType;
use Override;

class UploadDocumentRequest extends FormRequest
{
    /**
     * Authorization is handled by UserPolicy (uploadDocument) in the controller.
     * Policy allows 'Edit User' permission OR own-profile uploads.
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
            'document_type' => ['required', 'string', Rule::in(DocumentType::values())],
            'document_number' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'back_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
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
            'document_type.required' => __('user::user.errors.document_type_required'),
            'document_type.in' => __('user::user.errors.document_type_invalid'),
            'file.required' => __('user::user.errors.document_file_required'),
            'file.mimes' => __('user::user.errors.document_file_mimes'),
            'file.max' => __('user::user.errors.document_file_max'),
            'back_file.mimes' => __('user::user.errors.document_back_file_mimes'),
            'back_file.max' => __('user::user.errors.document_back_file_max'),
            'expiry_date.after' => __('user::user.errors.document_expiry_after'),
        ];
    }
}
