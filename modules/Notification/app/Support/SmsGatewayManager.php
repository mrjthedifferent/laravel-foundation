<?php

declare(strict_types=1);

namespace Modules\Notification\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\ActivityLog\Actions\CreateSmsLogAction;
use Modules\Settings\Data\SmsGatewayData;
use Modules\Settings\Services\MailerSecretCipher;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Contracts\SmsGateway;
use Override;
use RuntimeException;
use Throwable;

/**
 * Picks between a log (dry-run) driver and a generic-HTTP driver based on
 * the currently configured sms_gateway setting, and owns every gateway's
 * shared request/response/logging shape.
 *
 * ActivityLog's CreateSmsLogAction is resolved lazily, guarded by
 * module('ActivityLog'), rather than constructor-injected: ActivityLog is
 * not a hard dependency of sending an SMS, so a project running without it
 * must still be able to construct this class at all.
 */
final class SmsGatewayManager implements SmsGateway
{
    private const int REQUEST_TIMEOUT = 60;

    private const int CONNECT_TIMEOUT = 30;

    public function __construct(
        private readonly SettingsRepository $settings,
    ) {}

    #[Override]
    public function send(string $phone, string $message): void
    {
        // SMS gateways expect the country code without a leading '+' (numbers are
        // stored E.164, e.g. +8801712345678 -> 8801712345678).
        $phone = ltrim($phone, '+');

        $smsGateways = $this->settings->fresh('sms_gateways');

        if (! is_array($smsGateways)) {
            throw new RuntimeException('SMS Gateways are not configured.');
        }

        $selectedType = $this->settings->fresh('sms_gateway');
        $smsSetting = collect($smsGateways)->firstWhere('TYPE', $selectedType);

        if (empty($smsSetting)) {
            Log::channel('daily_sms')->error('SMS Gateway not found', [
                'status' => 'failed',
                'phone' => $phone,
                'message' => $message,
            ]);

            return;
        }

        if ($smsSetting['TYPE'] === 'log') {
            $this->sendViaLog($phone, $message);

            return;
        }

        $this->sendViaHttp($phone, $message, $smsSetting);
    }

    private function sendViaLog(string $phone, string $message): void
    {
        Log::channel('daily_sms')->info('SMS Logged (dry-run)', [
            'status' => 'success',
            'phone' => $phone,
            'message' => $message,
        ]);

        if (module('ActivityLog')) {
            app(CreateSmsLogAction::class)->execute($phone, $message, []);
        }
    }

    /**
     * @param  array<string, mixed>  $smsSetting
     */
    private function sendViaHttp(string $phone, string $message, array $smsSetting): void
    {
        // Header/param values are stored encrypted; decrypt before calling out.
        $settings = SmsGatewayData::decryptValue($smsSetting['VALUE'], new MailerSecretCipher);
        $url = $settings['endpoint'] ?? '';
        $method = strtoupper($settings['method'] ?? '');
        $mobileKey = $settings['mobile_key'] ?? '';
        $messageKey = $settings['message_key'] ?? '';
        $headers = (array) ($settings['headers'] ?? []);
        $extraParams = (array) ($settings['params'] ?? []);

        if (! empty($settings['mobile_prefix'])) {
            $phone = $settings['mobile_prefix'].$phone;
        }

        if (! $url || ! $method || ! $mobileKey || ! $messageKey) {
            Log::channel('daily_sms')->error('SMS Gateway settings are incomplete', [
                'sms' => ['phone' => $phone, 'message' => $message],
                'sms_setting' => $settings,
            ]);

            return;
        }

        $params = [
            $mobileKey => $phone,
            $messageKey => $message,
        ];

        foreach ($extraParams as $key => $value) {
            $params[$key] = ($key === 'csms_id') ? 'app_'.Str::random(10) : $value;
        }

        try {
            $request = Http::withHeaders($headers)
                ->timeout(self::REQUEST_TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT);

            $response = $method === 'POST'
                ? $request->post($url, $params)
                : $request->get($url, $params);

            $result = $response->json() ?? $response->body();

            if ($response->failed()) {
                Log::channel('daily_sms')->error('SMS gateway returned an error', [
                    'status' => $response->status(),
                    'phone' => $phone,
                ]);
            }

            if (module('ActivityLog')) {
                $smsLog = app(CreateSmsLogAction::class)->execute($phone, $message, $result);
                Log::channel('daily_sms')->info('SMS sent', $smsLog->toArray());
            } else {
                Log::channel('daily_sms')->info('SMS sent', [
                    'phone' => $phone,
                    'message' => $message,
                ]);
            }
        } catch (Throwable $e) {
            Log::channel('daily_sms')->error('Error sending SMS', [
                'error' => $e->getMessage(),
                'phone' => $phone,
                'message' => $message,
            ]);
        }
    }
}
