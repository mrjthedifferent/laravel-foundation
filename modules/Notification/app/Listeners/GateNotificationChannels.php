<?php

namespace Modules\Notification\Listeners;

use Illuminate\Notifications\Events\NotificationSending;
use Modules\Notification\Support\NotificationToggleRegistry;

/**
 * Central chokepoint that switches automatic notifications on/off per
 * channel (mail, database/in-app, fcm/push, sms; broadcast follows the
 * in-app toggle since it is the real-time delivery of the same surface).
 *
 * Laravel fires NotificationSending once per channel before each send;
 * returning false cancels that channel. The registry entry's 'channels'
 * list bounds what is gateable and 'locked' channels are never cancelled
 * (e.g. OTP over SMS, which would break phone login).
 *
 * Unregistered notifications, unknown channels, and notifications whose
 * setting is missing always send (default-on) so there is no behavioural
 * regression.
 *
 * NOTE: the dispatcher uses events->until(), which halts on the first
 * non-null return — this must remain the only NotificationSending
 * listener that returns a value.
 */
class GateNotificationChannels
{
    public function handle(NotificationSending $event): bool
    {
        if (! isset(NotificationToggleRegistry::CHANNEL_PREFIXES[$event->channel])) {
            return true;
        }

        $capability = $event->channel === 'broadcast' ? 'database' : $event->channel;

        $entry = NotificationToggleRegistry::resolveEntry($event->notification);

        if ($entry === null) {
            return true;
        }

        if (! NotificationToggleRegistry::supports($entry, $capability)) {
            return true;
        }

        if (NotificationToggleRegistry::isLocked($entry, $capability)) {
            return true;
        }

        $key = NotificationToggleRegistry::settingKey($entry, $capability);

        // Read the toggle live rather than from config('settings.{key}.value'). The Settings
        // provider fills config once at boot, but queued notifications are sent by long-lived
        // workers: a worker started before a toggle was switched off kept the old value for the
        // life of the process, so the notification carried on sending until the next deploy
        // restarted the queue. Missing setting = default-on, unchanged.
        return NotificationToggleRegistry::isEnabled($key) ?? true;
    }
}
