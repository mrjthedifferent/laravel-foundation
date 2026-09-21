<?php

use Modules\Notification\Support\NotificationToggleRegistry;

/*
|--------------------------------------------------------------------------
| Notification Module Settings
|--------------------------------------------------------------------------
|
| Define settings seeded into the settings table for this module.
| The per-channel notification on/off toggles are generated from the shared
| registry in app/Support/notification_toggles.php so the two never drift
| apart. Each toggle defaults to "1" (on) so existing behaviour is
| preserved. Locked channels (always on) get no setting at all.
|
| Mail keys keep the legacy mail_notify_{type} names (and the
| "Email Notifications" group) so existing settings rows are reused as-is.
|
*/

return NotificationToggleRegistry::settingsFor(require __DIR__.'/../app/Support/notification_toggles.php');
