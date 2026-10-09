<?php

namespace Modules\Notification\Channels;

use Exception;
use Google_Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mrj\Foundation\Contracts\PushSender;
use Mrj\Foundation\Contracts\SettingsRepository;
use Override;
use Throwable;

class FcmChannel implements PushSender
{
    /** FCM v1 send endpoint template */
    private const string FCM_URL = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    /** Maximum tokens sent concurrently per chunk */
    private const int TOKEN_BATCH_SIZE = 500;

    private const int REQUEST_TIMEOUT = 30;

    #[Override]
    public function send(object $notifiable, object $notification): bool
    {
        if (! method_exists($notification, 'toFcm')) {
            Log::channel('daily_notification')->error('Notification does not have toFcm method.');

            return false;
        }

        $message = $notification->toFcm($notifiable);
        $tokens = array_values(array_filter((array) ($message['to'] ?? [])));

        if (empty($tokens)) {
            return true;
        }

        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            Log::channel('daily_notification')->error('Failed to retrieve FCM access token.');

            return false;
        }

        $projectId = $this->projectId();
        if ($projectId === '') {
            Log::channel('daily_notification')->error('Firebase project ID is not configured (Settings → Firebase).');

            return false;
        }

        $url = sprintf(self::FCM_URL, $projectId);
        $headers = [
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ];

        $notificationPayload = array_filter($message['notification'], static fn ($v) => $v !== null && $v !== '');
        $dataPayload = ! empty($message['data']) ? $this->stringifyValues($message['data']) : null;
        $android = ['priority' => 'high', 'notification' => array_filter([
            'channel_id' => config('notification.android_channel', 'general'),
            'sound' => 'default',
        ])];

        $responses = [];
        $errors = [];
        $deadTokens = [];

        // Sent concurrently per chunk via Http::pool(), rather than one request
        // after another — the previous sequential loop only ever "batched" in
        // the sense of grouping tokens for logging, not in how the requests
        // actually went out.
        foreach (array_chunk($tokens, self::TOKEN_BATCH_SIZE) as $chunk) {
            $chunkResponses = Http::pool(fn ($pool) => collect($chunk)->map(
                fn ($token) => $pool->withHeaders($headers)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->post($url, [
                        'message' => array_filter([
                            'token' => $token,
                            'notification' => $notificationPayload,
                            'data' => $dataPayload,
                            'android' => $android,
                        ]),
                    ])
            )->all());

            foreach ($chunkResponses as $i => $response) {
                if ($response instanceof Throwable) {
                    $errors[] = $response->getMessage();

                    continue;
                }

                if ($response->failed()) {
                    if ($this->isDeadToken($response->json())) {
                        $deadTokens[] = $chunk[$i];
                    }
                    $errors[] = 'HTTP '.$response->status().': '.$response->body();

                    continue;
                }

                $responses[] = $response->json();
            }
        }

        if ($deadTokens !== [] && method_exists($notifiable, 'forgetPushTokens')) {
            $notifiable->forgetPushTokens($deadTokens);
        }

        $context = ['phone' => $notifiable->phone ?? null, 'message' => $message];

        if (! empty($errors)) {
            Log::channel('daily_notification')->error(json_encode(
                array_merge($context, ['status' => 'Failed to send FCM notification.', 'errors' => $errors]),
                JSON_THROW_ON_ERROR
            ));
        }

        if (! empty($responses)) {
            Log::channel('daily_notification')->info(json_encode(
                array_merge($context, ['status' => 'FCM notification sent successfully.', 'result' => $responses]),
                JSON_THROW_ON_ERROR
            ));
        }

        return true;
    }

    /**
     * Every data-payload value must be a string (FCM requirement): scalars are cast, arrays and
     * objects are sent as JSON, and nulls are left out.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function stringifyValues(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $out[(string) $key] = match (true) {
                is_bool($value) => $value ? '1' : '0',
                is_scalar($value) => (string) $value,
                default => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
            };
        }

        return $out;
    }

    /**
     * FCM's answer for a token that will never work again: the app was uninstalled or the token
     * rotated (UNREGISTERED), or it is not a token at all (INVALID_ARGUMENT naming the token).
     *
     * @param  array<string, mixed>|null  $body
     */
    private function isDeadToken(?array $body): bool
    {
        $error = $body['error'] ?? [];
        foreach ((array) ($error['details'] ?? []) as $detail) {
            if (($detail['errorCode'] ?? null) === 'UNREGISTERED') {
                return true;
            }
        }

        return ($error['status'] ?? null) === 'NOT_FOUND'
            || (($error['status'] ?? null) === 'INVALID_ARGUMENT' && str_contains((string) ($error['message'] ?? ''), 'registration token'));
    }

    /**
     * Settings → Firebase first, then FIREBASE_PROJECT_ID, then the service account itself
     * (its JSON names the project).
     */
    public function projectId(): string
    {
        $settings = app(SettingsRepository::class);
        $fromSettings = trim((string) $settings->get('firebase_project_id'));
        if ($fromSettings !== '') {
            return $fromSettings;
        }

        $fromEnv = trim((string) config('notification.project_id'));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $credentials = json_decode((string) $settings->get('firebase_credentials_json'), true);

        return is_array($credentials) ? trim((string) ($credentials['project_id'] ?? '')) : '';
    }

    private function getAccessToken(): ?string
    {
        $cacheKey = 'firebase_access_token';

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $credentialsJson = app(SettingsRepository::class)->get('firebase_credentials_json');
        if (empty($credentialsJson)) {
            Log::channel('daily_notification')->error('Firebase credentials JSON is not configured.');

            return null;
        }

        try {
            $credentials = json_decode($credentialsJson, true, 512, JSON_THROW_ON_ERROR);

            $client = new Google_Client;
            $client->setAuthConfig($credentials);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->addScope('https://www.googleapis.com/auth/cloud-platform');

            $token = $client->fetchAccessTokenWithAssertion();
            $accessToken = $token['access_token'] ?? null;

            if ($accessToken) {
                Cache::put($cacheKey, $accessToken, $token['expires_in'] ?? 3600);
            }

            return $accessToken;
        } catch (Exception $e) {
            Log::channel('daily_notification')->error('Failed to get FCM access token: '.$e->getMessage());

            return null;
        }
    }
}
