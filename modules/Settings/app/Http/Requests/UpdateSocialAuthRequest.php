<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'google_client_id' => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:255'],
            'google_redirect_uri' => ['nullable', 'string', 'max:255'],
            'github_client_id' => ['nullable', 'string', 'max:255'],
            'github_client_secret' => ['nullable', 'string', 'max:255'],
            'github_redirect_uri' => ['nullable', 'string', 'max:255'],
            'apple_client_id' => ['nullable', 'string', 'max:255'],
            'apple_client_secret' => ['nullable', 'string', 'max:255'],
            'apple_redirect_uri' => ['nullable', 'string', 'max:255'],
            'apple_team_id' => ['nullable', 'string', 'max:255'],
            'apple_key_id' => ['nullable', 'string', 'max:255'],
            'apple_key_file' => ['nullable', 'string', 'max:255'],
        ];
    }
}
