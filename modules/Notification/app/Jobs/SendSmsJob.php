<?php

namespace Modules\Notification\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\ActivityLog\Actions\CreateSmsLogAction;
use Modules\Settings\Data\SmsGatewayData;
use Modules\Settings\Services\MailerSecretCipher;
use RuntimeException;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    private const int REQUEST_TIMEOUT = 60;

    private const int CONNECT_TIMEOUT = 30;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        protected string $message,
        protected string $phone,
    ) {}

    public function handle(): void
    {
        // SMS gateways expect the country code without a leading '+' (numbers are
        // stored E.164, e.g. +8801712345678 -> 8801712345678).
        $this->phone = ltrim($this->phone, '+');

        $smsGateways = getSystemSetting('sms_gateways');

        if (! is_array($smsGateways)) {
            throw new RuntimeException('SMS Gateways are not configured.');
        }

        $selectedType = getSystemSetting('sms_gateway');
        $smsSetting = collect($smsGateways)->firstWhere('TYPE', $selectedType);

        if (empty($smsSetting)) {
            Log::channel('daily_sms')->error('SMS Gateway not found', [
                'status' => 'failed',
                'phone' => $this->phone,
                'message' => $this->message,
            ]);

            return;
        }

        if ($smsSetting['TYPE'] === 'log') {
            Log::channel('daily_sms')->info('SMS Logged (dry-run)', [
                'status' => 'success',
                'phone' => $this->phone,
                'message' => $this->message,
            ]);

            $smsLog = app(CreateSmsLogAction::class)->execute($this->phone, $this->message, []);

            return;
        }

        // Header/param values are stored encrypted; decrypt before calling out.
        $settings = SmsGatewayData::decryptValue($smsSetting['VALUE'], new MailerSecretCipher);
        $url = $settings['endpoint'] ?? '';
        $method = strtoupper($settings['method'] ?? '');
        $mobileKey = $settings['mobile_key'] ?? '';
        $messageKey = $settings['message_key'] ?? '';
        $headers = (array) ($settings['headers'] ?? []);
        $extraParams = (array) ($settings['params'] ?? []);

        if (! empty($settings['mobile_prefix'])) {
            $this->phone = $settings['mobile_prefix'].$this->phone;
        }

        if (! $url || ! $method || ! $mobileKey || ! $messageKey) {
            Log::channel('daily_sms')->error('SMS Gateway settings are incomplete', [
                'sms' => ['phone' => $this->phone, 'message' => $this->message],
                'sms_setting' => $settings,
            ]);

            return;
        }

        $params = [
            $mobileKey => $this->phone,
            $messageKey => $this->message,
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
                    'phone' => $this->phone,
                ]);
            }

            if (module('ActivityLog')) {
                $smsLog = app(CreateSmsLogAction::class)->execute($this->phone, $this->message, $result);
                Log::channel('daily_sms')->info('SMS sent', $smsLog->toArray());
            } else {
                Log::channel('daily_sms')->info('SMS sent', [
                    'phone' => $this->phone,
                    'message' => $this->message,
                ]);
            }
        } catch (Exception $e) {
            Log::channel('daily_sms')->error('Error sending SMS', [
                'error' => $e->getMessage(),
                'phone' => $this->phone,
                'message' => $this->message,
            ]);
        }
    }
}
