<?php

namespace Modules\Notification\Concerns;

use Modules\Notification\Channels\FcmChannel;

/**
 * Default FCM channel representation for notifications.
 *
 * Token resolution is delegated to the notifiable's routeNotificationForFcm(),
 * so notifications only declare what to say via {@see fcmPayload()} — not how to
 * fetch device tokens. The returned shape matches the contract expected by
 * {@see FcmChannel::send()}.
 */
trait SendsFirebasePush
{
    /**
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        $payload = $this->fcmPayload($notifiable);

        return [
            'to' => method_exists($notifiable, 'routeNotificationForFcm')
                ? $notifiable->routeNotificationForFcm()
                : [],
            'notification' => [
                'title' => $payload['title'],
                'body' => $payload['body'],
            ],
            'data' => $payload['data'] ?? [],
        ];
    }

    /**
     * The push title, body and optional data payload.
     *
     * @return array{title: string, body: string, data?: array<string, mixed>}
     */
    abstract protected function fcmPayload(object $notifiable): array;
}
