<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Settings\Models\Setting;
use Override;

class UpdateLoginPageSettingsRequest extends FormRequest
{
    /** Phosphor 2: a weight class and the icon, e.g. "ph ph-shield-check" or "ph-fill ph-star". */
    public const string ICON_PATTERN = '/^ph(-thin|-light|-bold|-fill|-duotone)? ph-[a-z0-9-]+$/';

    public const int MAX_HIGHLIGHTS = 6;

    /** Checked here as well as in the controller, so a refused user gets 403 before validation runs. */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('editSpecial', Setting::class);
    }

    public function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:120'],
            'subheadline' => ['nullable', 'string', 'max:200'],
            'highlights_mode' => ['required', Rule::in(['default', 'custom'])],
            'highlights' => ['nullable', 'array', 'max:'.self::MAX_HIGHLIGHTS],
            'highlights.*.icon' => ['nullable', 'string', 'max:60', 'regex:'.self::ICON_PATTERN],
            'highlights.*.text' => ['nullable', 'string', 'max:120'],
            'mark' => ['required', Rule::in(['auto', 'monogram', 'none'])],
            'show_name' => ['nullable', 'boolean'],
            'graphic' => ['required', Rule::in(['bars', 'glow', 'none'])],
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'highlights.*.icon' => __('settings::settings.special_login_page.icon_label'),
            'highlights.*.text' => __('settings::settings.special_login_page.text_label'),
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'highlights.*.icon.regex' => __('settings::settings.special_login_page.icon_invalid'),
        ];
    }
}
