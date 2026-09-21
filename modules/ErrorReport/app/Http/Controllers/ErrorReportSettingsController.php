<?php

namespace Modules\ErrorReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Modules\ErrorReport\Http\Requests\UpdateErrorReportSettingsRequest;
use Modules\Settings\Models\Setting;
use Modules\Settings\Providers\SettingsServiceProvider;
use Mrj\Foundation\Http\Controllers\Controller;

class ErrorReportSettingsController extends Controller
{
    private const array KEYS = [
        'error_report_enabled',
        'error_report_channels',
        'error_report_email_recipients',
        'error_report_slack_webhook',
        'error_report_telegram_bot_token',
        'error_report_telegram_chat_id',
        'error_report_throttle_minutes',
        'error_report_dont_report',
    ];

    public function index(): Renderable
    {
        $this->authorize('editErrorReportSettings');

        $settings = Setting::whereIn('key', self::KEYS)->get()->keyBy('key');

        return view('errorreport::settings', compact('settings'));
    }

    public function update(UpdateErrorReportSettingsRequest $request): RedirectResponse
    {
        $this->authorize('editErrorReportSettings');

        $validated = $request->validated();

        $this->saveSetting('error_report_enabled', $validated['error_report_enabled'] ? '1' : '0');
        $this->saveSetting('error_report_channels', json_encode($validated['error_report_channels'] ?? []));
        $this->saveSetting('error_report_email_recipients', $validated['error_report_email_recipients'] ?? '');
        $this->saveSetting('error_report_slack_webhook', $validated['error_report_slack_webhook'] ?? '', 'encrypted');
        $this->saveSetting('error_report_telegram_bot_token', $validated['error_report_telegram_bot_token'] ?? '', 'encrypted');
        $this->saveSetting('error_report_telegram_chat_id', $validated['error_report_telegram_chat_id'] ?? '');
        $this->saveSetting('error_report_throttle_minutes', (string) ($validated['error_report_throttle_minutes'] ?? 60));
        $dontReport = $validated['error_report_dont_report'] ?? '';
        $dontReportArray = array_values(array_filter(array_map('trim', explode("\n", (string) $dontReport))));
        $this->saveSetting('error_report_dont_report', json_encode($dontReportArray));

        Cache::forget(SettingsServiceProvider::cacheKey());

        return redirect()->back()->with('success', 'Error Report settings updated successfully');
    }

    private function saveSetting(string $key, string $value, string $type = 'text'): void
    {
        // 'type' must be filled before 'value': Setting::setValueAttribute()
        // reads the sibling 'type' attribute to decide whether to encrypt, and
        // Eloquent's fill() assigns attributes in array order.
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'type' => $type,
                'value' => $value,
                'group' => 'Error Report',
                'is_visible' => false,
            ]
        );
    }
}
