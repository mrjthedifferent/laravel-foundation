<?php

namespace Modules\ErrorReport\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateErrorReportSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'error_report_enabled' => ['boolean'],
            'error_report_channels' => ['array'],
            'error_report_channels.*' => ['string', 'in:mail,slack,telegram'],
            'error_report_email_recipients' => ['nullable', 'string', 'max:500'],
            'error_report_slack_webhook' => ['nullable', 'string', 'max:500'],
            'error_report_telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'error_report_telegram_chat_id' => ['nullable', 'string', 'max:50'],
            'error_report_throttle_minutes' => ['integer', 'min:1', 'max:10080'],
            'error_report_dont_report' => ['nullable', 'string'],
        ];
    }
}
