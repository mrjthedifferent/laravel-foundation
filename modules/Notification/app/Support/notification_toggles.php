<?php

/*
|--------------------------------------------------------------------------
| Toggleable Automatic Notifications (all channels)
|--------------------------------------------------------------------------
|
| Single source of truth for the "Notification Settings" page and the
| GateNotificationChannels listener. Each entry maps an automatic
| notification to per-channel boolean setting keys derived from 'type'
| via NotificationToggleRegistry::settingKey() — e.g. mail_notify_{type},
| inapp_notify_{type}, push_notify_{type}, sms_notify_{type}. When a
| setting is off, the listener cancels ONLY that channel. The broadcast
| channel follows the in-app (database) toggle since it is the real-time
| delivery of the same in-app surface.
|
| Entry shape:
|   - 'type'     : slug the setting keys are derived from.
|   - 'label'    : human label shown on the settings page.
|   - 'group'    : domain group heading on the settings page.
|   - 'class'    : matched against get_class($notification).
|   - 'app_type' : for the generic AppNotification, matched against the
|                  notification's data['type'] value.
|   - 'channels' : channels this notification can actually deliver on
|                  (from its via()); only these get toggles/settings.
|   - 'locked'   : channels that must stay on (rendered disabled in the
|                  UI, never gated, no setting persisted).
|
| Another module adds its own entries by shipping config/notification_toggles.php
| (same entry shape) and appending NotificationToggleRegistry::settingsFor() of
| that list to its config/settings.php.
|
| NOTE: This file lives outside config/ on purpose. The module's config
| auto-loader recursively merges every *.php under config/, so keeping it
| here (required by path from config/config.php and config/settings.php)
| avoids the list being merged twice and duplicated.
|
*/

use Modules\Notification\Notifications\AppNotification;

return [
    // Account & Security
    ['type' => 'password_reset', 'label' => 'Password Reset', 'group' => 'Account & Security', 'class' => AppNotification::class, 'app_type' => 'password_reset', 'channels' => ['mail', 'database', 'fcm', 'sms']],
    ['type' => 'roles_changed', 'label' => 'Role Changed', 'group' => 'Account & Security', 'class' => AppNotification::class, 'app_type' => 'roles_changed', 'channels' => ['mail', 'database', 'fcm', 'sms']],
    ['type' => 'account_deactivated', 'label' => 'Account Deactivated', 'group' => 'Account & Security', 'class' => AppNotification::class, 'app_type' => 'account_deactivated', 'channels' => ['mail', 'database', 'fcm', 'sms']],
    ['type' => 'new_device_login', 'label' => 'New Device Login Alert', 'group' => 'Account & Security', 'class' => AppNotification::class, 'app_type' => 'new_device_login', 'channels' => ['mail', 'database', 'fcm', 'sms']],
];
