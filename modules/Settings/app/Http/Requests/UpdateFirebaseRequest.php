<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFirebaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'firebase_credentials_json' => ['nullable', 'string'],
            'firebase_project_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
