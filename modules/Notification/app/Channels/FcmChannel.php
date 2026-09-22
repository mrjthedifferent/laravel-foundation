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

        $url = sprintf(self::FCM_URL, config('notification.project_id'));
        $headers = [
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ];

        $notificationPayload = $message['notification'];
        $dataPayload = ! empty($message['data']) ? $this->stringifyValues($message['data']) : null;

        $responses = [];
        $errors = [];

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
                        ]),
                    ])
            )->all());

            foreach ($chunkResponses as $response) {
                if ($response instanceof Throwable) {
                    $errors[] = $response->getMessage();

                    continue;
                }

                if ($response->failed()) {
                    $errors[] = 'HTTP '.$response->status().': '.$response->body();

                    continue;
                }

                $responses[] = $response->json();
            }
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

    /** Cast every data-payload value to string (FCM requirement). */
    private function stringifyValues(array $data): array
    {
        return array_map(static fn ($v) => (string) $v, $data);
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
