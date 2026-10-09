<?php

namespace Modules\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Notification\Concerns\SendsFirebasePush;
use Modules\Notification\Enum\NotificationType;

class AppNotification extends Notification
{
    use Queueable;
    use SendsFirebasePush;

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>|null  $channels  null = use config default
     * @param  Mailable|null  $mailTemplate  When provided, used as-is for the mail channel.
     *                                       When null, a plain MailMessage is built from $title/$body.
     * @param  string|null  $image  A public image URL shown in the push (Android big picture).
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly NotificationType $type = NotificationType::Info,
        public readonly array $data = [],
        public readonly ?array $channels = null,
        public readonly ?Mailable $mailTemplate = null,
        public readonly ?string $image = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = array_values($this->channels ?? config('notification.channels', ['database', 'fcm']));

        // The stored row goes first so the push can carry its id (the app marks it read on tap).
        usort($channels, static fn (string $a, string $b): int => ($b === 'database') <=> ($a === 'database'));

        if (! in_array('broadcast', $channels, true)
            && config('broadcasting.default') !== 'null') {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }

    /**
     * The push carries the caller's data plus `notification_id` (the in-app row, which shares
     * this notification's id) so a tap can open and mark the right item.
     *
     * @return array{title: string, body: string, image: string|null, data: array<string, mixed>}
     */
    protected function fcmPayload(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'image' => $this->image,
            'data' => array_filter([...$this->data, 'notification_id' => $this->id], static fn ($v) => $v !== null),
        ];
    }

    /**
     * Deliver via mail channel.
     *
     * Returns the injected Mailable when one is provided — giving the caller
     * full control over the email template, layout, attachments, etc.
     * Falls back to a simple MailMessage built from $title / $body otherwise.
     */
    public function toMail(object $notifiable): Mailable|MailMessage
    {
        if ($this->mailTemplate !== null) {
            return $this->mailTemplate;
        }

        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body);
    }

    /**
     * Returns the SMS message string.
     * Channels expecting an array should cast as needed.
     */
    public function toSms(object $notifiable): string
    {
        return "{$this->title}: {$this->body}";
    }

    /**
     * Get the array representation of the notification for broadcast.
     * Include 'broadcast' in via() to enable real-time web delivery.
     *
     * @return array<string, mixed>
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type->value,
            'data' => $this->data,
        ];
    }
}
