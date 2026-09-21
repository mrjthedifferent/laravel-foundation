<?php

return [
    'name' => 'Notification',

    /*
    |--------------------------------------------------------------------------
    | Default Notification Channels
    |--------------------------------------------------------------------------
    | Comma-separated list of channels used when no explicit channel list is
    | passed to AppNotification. Supported: "database", "fcm", "mail", "sms", "broadcast".
    | Add "broadcast" for real-time web notifications (requires BROADCAST_CONNECTION).
    */
    'channels' => array_map('trim', explode(',', env('NOTIFICATION_CHANNELS', 'database,fcm'))),

    /*
    |--------------------------------------------------------------------------
    | Toggleable Automatic Notifications
    |--------------------------------------------------------------------------
    | Registry of automatic notifications whose channels (mail, in-app, push,
    | sms) can be switched on/off from the Settings module. Single source of
    | truth shared by the GateNotificationChannels listener and the
    | Notification Settings page. Kept outside config/ so the module's
    | recursive config auto-loader does not also merge it (which would
    | duplicate the list). See app/Support/notification_toggles.php.
    */
    'toggles' => require __DIR__.'/../app/Support/notification_toggles.php',

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    | The Firebase/Google Cloud project ID used for FCM v1 API calls.
    | Set FIREBASE_PROJECT_ID in your .env file.
    */
    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    | The queue connection and queue name used by notification jobs.
    */
    'queue' => env('NOTIFICATION_QUEUE', 'notifications'),
    'queue_connection' => env('NOTIFICATION_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
];
