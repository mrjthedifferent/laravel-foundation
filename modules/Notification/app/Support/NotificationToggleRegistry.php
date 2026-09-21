<?php

namespace Modules\Notification\Support;

use Modules\Notification\Notifications\AppNotification;
use Modules\Settings\Providers\SettingsServiceProvider;
use Nwidart\Modules\Facades\Module;

/**
 * Read-side helpers for the notification toggle registry
 * (app/Support/notification_toggles.php, loaded as config('notification.toggles')).
 *
 * Shared by the GateNotificationChannels listener, the Notification module's
 * settings definitions, and the Settings module's notification settings page
 * so the three never drift apart.
 */
final readonly class NotificationToggleRegistry
{
    /**
     * Gateable channel => setting-key prefix. Broadcast maps to the in-app
     * (inapp) prefix because it is the real-time delivery of the same
     * in-app surface and shares its toggle.
     *
     * @var array<string, string>
     */
    public const CHANNEL_PREFIXES = [
        'mail' => 'mail',
        'database' => 'inapp',
        'fcm' => 'push',
        'sms' => 'sms',
        'broadcast' => 'inapp',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function entries(): array
    {
        $entries = config('notification.toggles', []);

        foreach (Module::allEnabled() as $module) {
            $entries = array_merge($entries, (array) config(strtolower($module->getName()).'.notification_toggles', []));
        }

        return $entries;
    }

    /**
     * The on/off settings for a list of toggle entries, for a module's
     * config/settings.php. Each defaults to "1" (on); locked channels get none.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<string, array<string, mixed>>
     */
    public static function settingsFor(array $entries): array
    {
        $descriptions = [
            'mail' => 'Send the "%s" email automatically',
            'database' => 'Deliver the "%s" in-app notification',
            'fcm' => 'Send the "%s" push notification',
            'sms' => 'Send the "%s" SMS',
        ];

        $settings = [];

        foreach ($entries as $entry) {
            foreach ($entry['channels'] as $channel) {
                if (self::isLocked($entry, $channel)) {
                    continue;
                }

                $settings[self::settingKey($entry, $channel)] = [
                    // Mail keeps the "Email Notifications" group so existing rows are reused.
                    'group' => $channel === 'mail' ? 'Email Notifications' : 'Notification Channels',
                    'value' => '1',
                    'type' => 'boolean',
                    'description' => sprintf($descriptions[$channel], $entry['label']),
                    'is_visible' => false,
                ];
            }
        }

        return $settings;
    }

    /**
     * The boolean setting key controlling the given channel of an entry,
     * e.g. mail_notify_password_reset, inapp_notify_password_reset.
     *
     * @param  array<string, mixed>  $entry
     */
    public static function settingKey(array $entry, string $channel): string
    {
        return self::CHANNEL_PREFIXES[$channel].'_notify_'.$entry['type'];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    public static function supports(array $entry, string $channel): bool
    {
        return in_array($channel, $entry['channels'] ?? [], true);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    public static function isLocked(array $entry, string $channel): bool
    {
        return in_array($channel, $entry['locked'] ?? [], true);
    }

    /**
     * The current value of a toggle, read from the settings cache rather than from
     * config('settings.*').
     *
     * SettingsServiceProvider hydrates config once, at boot. Queued notifications are sent by
     * long-lived workers, so a worker that started before a toggle was changed keeps the old
     * value in memory for the life of the process — switching a notification off in the UI had
     * no effect on it until the next deploy restarted the queue. The `app_settings` cache is
     * the same source the provider reads and is forgotten on every save, so reading it here
     * picks the change up on the next send with no restart.
     *
     * @return bool|null null when the setting does not exist (callers treat that as default-on)
     */
    public static function isEnabled(string $settingKey): ?bool
    {
        $value = collect(SettingsServiceProvider::cached())->firstWhere('key', $settingKey)['value'] ?? null;

        return $value === null ? null : (bool) $value;
    }

    /**
     * Resolve the registry entry for the given notification, or null when
     * the notification is not part of the toggleable registry.
     *
     * @return array<string, mixed>|null
     */
    public static function resolveEntry(object $notification): ?array
    {
        $class = $notification::class;
        $appType = $notification instanceof AppNotification
            ? ($notification->data['type'] ?? null)
            : null;

        foreach (self::entries() as $entry) {
            if (($entry['class'] ?? null) !== $class) {
                continue;
            }

            if (isset($entry['app_type'])) {
                if ($entry['app_type'] === $appType) {
                    return $entry;
                }

                continue;
            }

            return $entry;
        }

        return null;
    }
}
