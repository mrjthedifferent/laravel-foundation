<?php

namespace Modules\Notification\Channels;

use Google_Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mrj\Foundation\Traits\MyGuzzleClient;

class FcmChannel
{
    use MyGuzzleClient;

    /** FCM v1 send endpoint template */
    private const FCM_URL = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    /** Maximum tokens per FCM send call */
    private const TOKEN_BATCH_SIZE = 500;

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

        foreach (array_chunk($tokens, self::TOKEN_BATCH_SIZE) as $chunk) {
            foreach ($chunk as $token) {
                $body = [
                    'message' => array_filter([
                        'token' => $token,
                        'notification' => $notificationPayload,
                        'data' => $dataPayload,
                    ]),
                ];

                try {
                    $responses[] = $this->guzzle_post_call_json($body, $url, $headers);
                } catch (GuzzleException|\Exception $e) {
                    $errors[] = $e->getMessage();
                }
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

        $credentialsJson = config('settings.firebase_credentials_json.value');
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
        } catch (\Exception $e) {
            Log::channel('daily_notification')->error('Failed to get FCM access token: '.$e->getMessage());

            return null;
        }
    }
}
