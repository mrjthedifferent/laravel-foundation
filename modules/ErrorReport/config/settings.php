<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ErrorReport Module Settings
|--------------------------------------------------------------------------
|
| Define settings seeded into the settings table for this module.
| These control error capture, notification channels, and throttling.
|
*/
/*
|--------------------------------------------------------------------------
| All Error Report settings are invisible in the general settings page.
| They are managed exclusively via the Error Report module's settings page.
|--------------------------------------------------------------------------
*/
return [
    'error_report_enabled' => [
        'group' => 'Error Report',
        'value' => '1',
        'type' => 'boolean',
        'description' => 'Enable error capture and notifications',
        'is_visible' => false,
    ],
    'error_report_channels' => [
        'group' => 'Error Report',
        'value' => '["mail","slack","telegram"]',
        'type' => 'json',
        'description' => 'Notification channels: mail, slack, telegram',
        'is_visible' => false,
    ],
    'error_report_email_recipients' => [
        'group' => 'Error Report',
        'value' => '',
        'type' => 'text',
        'description' => 'Comma-separated email addresses (fallback: development_support_email)',
        'is_visible' => false,
    ],
    'error_report_slack_webhook' => [
        'group' => 'Error Report',
        'value' => '',
        'type' => 'text',
        'description' => 'Slack webhook URL (fallback: LOG_SLACK_WEBHOOK_URL)',
        'is_visible' => false,
    ],
    'error_report_telegram_bot_token' => [
        'group' => 'Error Report',
        'value' => '',
        'type' => 'text',
        'description' => 'Telegram Bot API token from @BotFather',
        'is_visible' => false,
    ],
    'error_report_telegram_chat_id' => [
        'group' => 'Error Report',
        'value' => '',
        'type' => 'text',
        'description' => 'Telegram chat/group ID to receive alerts',
        'is_visible' => false,
    ],
    'error_report_throttle_minutes' => [
        'group' => 'Error Report',
        'value' => '60',
        'type' => 'integer',
        'description' => 'Minutes before re-notifying for the same error',
        'is_visible' => false,
    ],
    'error_report_dont_report' => [
        'group' => 'Error Report',
        'value' => '[]',
        'type' => 'json',
        'description' => 'Exception class names to ignore (JSON array)',
        'is_visible' => false,
    ],
];
