<?php

namespace Modules\Notification\Actions;

use Illuminate\Mail\Mailable;
use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Jobs\BroadcastNotificationJob;
use Modules\Notification\Notifications\AppNotification;

/**
 * Single entry point for all notification dispatching.
 *
 * Works with ANY notifiable model (User, Admin, Customer, …).
 * Channels are plain strings — pass any combination or omit to use the system default.
 *
 * Examples:
 *   // Uses config default channels (e.g. database + fcm)
 *   NotifyAction::toUser($user, 'Title', 'Body');
 *
 *   // Explicit channels — any combination
 *   NotifyAction::toUser($user, 'Title', 'Body', channels: ['database']);
 *   NotifyAction::toUser($user, 'Title', 'Body', channels: ['mail', 'sms']);
 *   NotifyAction::toUser($user, 'Title', 'Body', channels: ['database', 'fcm', 'mail']);
 *
 *   // With a custom Mailable email template
 *   NotifyAction::toUser($user, 'Title', 'Body', channels: ['mail'], mailTemplate: new WelcomeMail($user));
 *   NotifyAction::toUser($user, 'Title', 'Body', channels: ['database', 'mail'], mailTemplate: new InvoiceMail($invoice));
 *
 *   // Queued push to a single notifiable — defaults to ['fcm']
 *   NotifyAction::push($user, 'Title', 'Body');
 *   NotifyAction::push($user, 'Title', 'Body', channels: ['fcm', 'sms']);
 *
 *   // Queued push to ALL users — defaults to ['fcm']
 *   NotifyAction::broadcast('Title', 'Body');
 *   NotifyAction::broadcast('Title', 'Body', channels: ['fcm', 'sms']);
 */
final readonly class NotifyAction
{
    /**
     * Send a notification to any notifiable model synchronously.
     *
     * Omit $channels to use the system default (notification.channels config).
     * Pass a $mailTemplate (any Mailable) to use a full Blade email template
     * instead of the default plain-text mail fallback.
     *
     * @param  object  $notifiable  Any model using the Notifiable trait
     * @param  list<string>|null  $channels  e.g. ['database'], ['fcm','mail'], null = config default
     * @param  array<string, mixed>  $data
     * @param  Mailable|null  $mailTemplate  Custom Mailable; used only when 'mail' is in $channels
     */
    public static function toUser(
        object $notifiable,
        string $title,
        string $body,
        NotificationType $type = NotificationType::Info,
        array $data = [],
        ?array $channels = null,
        ?Mailable $mailTemplate = null,
    ): void {
        $notifiable->notify(new AppNotification(
            title: $title,
            body: $body,
            type: $type,
            data: $data,
            channels: $channels,
            mailTemplate: $mailTemplate,
        ));
    }

    /**
     * Queue a push notification to a specific notifiable.
     *
     * Defaults to ['fcm'] when no channels given.
     * Pass additional channels (e.g. ['fcm','sms']) to deliver on multiple.
     *
     * @param  list<string>  $channels
     * @param  array<string, mixed>  $data
     */
    public static function push(
        object $notifiable,
        string $title,
        string $body,
        array $data = [],
        array $channels = ['fcm'],
    ): void {
        BroadcastNotificationJob::dispatch(
            title: $title,
            body: $body,
            data: $data,
            userId: $notifiable->getKey(),
            channels: $channels,
        );
    }

    /**
     * Queue a push notification to ALL users. Defaults to ['fcm'] when no channels given.
     *
     * @param  list<string>  $channels
     * @param  array<string, mixed>  $data
     */
    public static function broadcast(
        string $title,
        string $body,
        array $data = [],
        array $channels = ['fcm'],
    ): void {
        BroadcastNotificationJob::dispatch(
            title: $title,
            body: $body,
            data: $data,
            channels: $channels,
        );
    }
}
