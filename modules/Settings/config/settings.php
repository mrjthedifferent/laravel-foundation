<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Settings Module - Application Settings
|--------------------------------------------------------------------------
|
| This file is intentionally empty. The Settings module's core application
| settings are defined in config/config.php and are seeded by
| SettingsSettingsSeeder, which delegates to that file.
|
| Add any additional settings that belong specifically to the Settings
| module's own configuration below.
|
*/
return [
    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */
    'app_name' => [
        'group' => 'General',
        'value' => 'App Name',
        'type' => 'text',
        'description' => 'The name of the application',
    ],
    'app_logo' => [
        'group' => 'General',
        'value' => null,
        'type' => 'image',
        'description' => 'The logo of the application',
    ],
    'favicon' => [
        'group' => 'General',
        'value' => null,
        'type' => 'image',
        'description' => 'The favicon of the application',
    ],
    'app_logo_small' => [
        'group' => 'General',
        'value' => null,
        'type' => 'image',
        'description' => 'The small logo of the application',
    ],
    'development_support_email' => [
        'group' => 'General',
        'value' => 'dev@example.com',
        'type' => 'text',
        'description' => 'The email address for development support',
    ],
    'maintenance_mode' => [
        'group' => 'General',
        'value' => '0',
        'type' => 'boolean',
        'description' => 'Enable maintenance mode',
    ],
    'maintenance_message' => [
        'group' => 'General',
        'value' => 'The site is under maintenance. Please try again later.',
        'type' => 'text',
        'description' => 'The message to display when in maintenance mode',
    ],
    'max_verification_attempts' => [
        'group' => 'OTP',
        'value' => '3',
        'type' => 'integer',
        'description' => 'The maximum number of verification code requests allowed before being blocked.',
    ],
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'api_token_idle_expiration_minutes' => [
        'group' => 'Security',
        'value' => '43200',
        'type' => 'integer',
        'description' => 'Minutes of inactivity before a mobile/API login token expires. Each request the user makes slides this window forward, so a continuously-used token never expires. Default 43200 (30 days).',
    ],
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'dashboard_cache_ttl_minutes' => [
        'group' => 'Performance',
        'value' => '10',
        'type' => 'integer',
        'description' => 'Minutes to cache the admin dashboard payload. The dashboard runs a large number of aggregate queries, so caching keeps it responsive. Set to 0 to disable caching and always show live figures.',
    ],
    /*
    |--------------------------------------------------------------------------
    | Mobile App Settings
    |--------------------------------------------------------------------------
    | For a project with a companion mobile app, which reads these at runtime
    | from GET /api/v1/settings/app.
    |--------------------------------------------------------------------------
    */

    // ── Branding & Identity ───────────────────────────────────────────────
    'app_name_mobile' => [
        'group' => 'Mobile App',
        'value' => env('APP_NAME', 'App'),
        'type' => 'text',
        'description' => 'App name displayed in the mobile application',
        'is_visible' => true,
    ],
    'app_tagline_mobile' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Tagline displayed below the app name on the splash and login screens',
        'is_visible' => true,
    ],
    'app_logo_mobile' => [
        'group' => 'Mobile App',
        'value' => null,
        'type' => 'image',
        'description' => 'Wide brand logo (splash, login hero)',
        'is_visible' => true,
    ],
    'app_icon' => [
        'group' => 'Mobile App',
        'value' => null,
        'type' => 'image',
        'description' => 'Square brand icon/badge',
        'is_visible' => true,
    ],
    'mobile_login_hero_image' => [
        'group' => 'Mobile App',
        'value' => null,
        'type' => 'image',
        'description' => 'Optional hero image shown on the login/splash screen',
        'is_visible' => true,
    ],

    // ── Theming ───────────────────────────────────────────────────────────
    'mobile_seed_color' => [
        'group' => 'Mobile App',
        'value' => '#2563EB',
        'type' => 'text',
        'description' => 'Primary seed color for the mobile app (hex, e.g. #2563EB). Drives the entire M3 color scheme — buttons, navigation, accents.',
        'is_visible' => true,
    ],
    'mobile_gradient_end_color' => [
        'group' => 'Mobile App',
        'value' => '#6D28D9',
        'type' => 'text',
        'description' => 'Brand gradient end color (hex, e.g. #6D28D9). Used in splash screen, login hero, and profile card stripe.',
        'is_visible' => true,
    ],
    'mobile_accent_color' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Optional tertiary accent color (hex). Leave blank to derive from the seed color.',
        'is_visible' => true,
    ],
    'mobile_default_theme_mode' => [
        'group' => 'Mobile App',
        'value' => 'system',
        'type' => 'select',
        'options' => json_encode(['system' => 'Follow System', 'light' => 'Light', 'dark' => 'Dark']),
        'description' => 'Default theme mode applied on first launch (before the user makes a choice)',
        'is_visible' => true,
    ],
    'mobile_allow_theme_toggle' => [
        'group' => 'Mobile App',
        'value' => '1',
        'type' => 'boolean',
        'description' => 'Allow users to switch theme mode (show the dark-mode control in app settings)',
        'is_visible' => true,
    ],
    'mobile_font_family' => [
        'group' => 'Mobile App',
        'value' => 'inter',
        'type' => 'select',
        'options' => json_encode(['inter' => 'Inter', 'roboto' => 'Roboto', 'poppins' => 'Poppins', 'system' => 'System Default']),
        'description' => 'Global font family for the mobile app',
        'is_visible' => true,
    ],

    // ── Feature flags: top-level modules ──────────────────────────────────

    // ── Feature flags: Leave sub-features ─────────────────────────────────

    // ── Feature flags: Attendance sub-features ────────────────────────────

    // ── Content & UX ──────────────────────────────────────────────────────
    'mobile_supported_languages' => [
        'group' => 'Mobile App',
        'value' => 'en',
        'type' => 'multi-select',
        'options' => json_encode(['en' => 'English', 'bn' => 'Bangla', 'ar' => 'Arabic', 'hi' => 'Hindi']),
        'description' => 'Languages selectable inside the mobile app',
        'is_visible' => true,
    ],
    'mobile_default_language' => [
        'group' => 'Mobile App',
        'value' => 'en',
        'type' => 'select',
        'options' => json_encode(['en' => 'English', 'bn' => 'Bangla', 'ar' => 'Arabic', 'hi' => 'Hindi']),
        'description' => 'Default language applied on first launch',
        'is_visible' => true,
    ],
    'mobile_support_email' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Support email surfaced inside the app (falls back to Contact email)',
        'is_visible' => true,
    ],
    'google_maps_api_key_android' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Google Maps API key (Map Tiles API) served to Android clients; blank falls back to OpenStreetMap',
        'is_visible' => true,
    ],
    'google_maps_api_key_ios' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Google Maps API key (Map Tiles API) served to iOS clients; blank falls back to OpenStreetMap',
        'is_visible' => true,
    ],
    'google_maps_api_key_web' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Google Maps API key (Map Tiles API) served to web clients; blank falls back to OpenStreetMap',
        'is_visible' => true,
    ],

    // ── Store, Version & Maintenance ──────────────────────────────────────
    'app_version' => [
        'group' => 'Mobile App',
        'value' => '1.0.0',
        'type' => 'text',
        'description' => 'The version of the application',
        'is_visible' => true,
    ],
    'android_app_code' => [
        'group' => 'Mobile App',
        'value' => '1',
        'type' => 'integer',
        'description' => 'Current Android build number',
        'is_visible' => true,
    ],
    'android_app_code_min' => [
        'group' => 'Mobile App',
        'value' => '1',
        'type' => 'integer',
        'description' => 'Minimum supported Android build number',
        'is_visible' => true,
    ],
    'ios_app_code' => [
        'group' => 'Mobile App',
        'value' => '1',
        'type' => 'integer',
        'description' => 'Current iOS build number',
        'is_visible' => true,
    ],
    'ios_app_code_min' => [
        'group' => 'Mobile App',
        'value' => '1',
        'type' => 'integer',
        'description' => 'Minimum supported iOS build number',
        'is_visible' => true,
    ],
    'play_store_url' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Google Play Store listing URL shown on the public app download page',
        'is_visible' => true,
    ],
    'app_store_url' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Apple App Store listing URL shown on the public app download page',
        'is_visible' => true,
    ],
    'mobile_apk_file' => [
        'group' => 'Mobile App',
        'value' => null,
        'type' => 'file',
        'description' => 'Direct Android APK download file for the public app download page',
        'is_visible' => true,
    ],
    'app_maintenance_mode' => [
        'group' => 'Mobile App',
        'value' => '0',
        'type' => 'boolean',
        'description' => 'Enable maintenance mode for the mobile application',
        'is_visible' => true,
    ],
    'app_maintenance_message' => [
        'group' => 'Mobile App',
        'value' => 'The app is temporarily down for maintenance. Please try again later.',
        'type' => 'textarea',
        'description' => 'Message shown in the mobile app while maintenance mode is on',
        'is_visible' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Settings
    | Managed via the Mobile App special-settings page (Contact tab). Hidden
    | from the generic settings form (is_visible = false). Consumed by the
    | mobile app via GET /api/v1/settings/app.
    |--------------------------------------------------------------------------
    */
    'contact_message' => [
        'group' => 'Contact',
        'value' => 'We are here to help you. Please contact us for any questions or concerns.',
        'type' => 'textarea',
        'is_visible' => false,
    ],
    'address' => [
        'group' => 'Contact',
        'value' => 'Address 1, Address 2',
        'type' => 'textarea',
        'is_visible' => false,
    ],
    'email' => [
        'group' => 'Contact',
        'value' => 'test@example.com',
        'type' => 'text',
        'is_visible' => false,
    ],
    'phone' => [
        'group' => 'Contact',
        'value' => '+1234567',
        'type' => 'text',
        'is_visible' => false,
    ],
    'web' => [
        'group' => 'Contact',
        'value' => 'https://example.com',
        'type' => 'text',
        'is_visible' => false,
    ],
    'facebook' => [
        'group' => 'Contact',
        'value' => '#',
        'type' => 'text',
        'is_visible' => false,
    ],
    'tiktok' => [
        'group' => 'Contact',
        'value' => '#',
        'type' => 'text',
        'is_visible' => false,
    ],
    'instagram' => [
        'group' => 'Contact',
        'value' => '#',
        'type' => 'text',
        'is_visible' => false,
    ],
    'twitter' => [
        'group' => 'Contact',
        'value' => '#',
        'type' => 'text',
        'is_visible' => false,
    ],
    'youtube' => [
        'group' => 'Contact',
        'value' => '#',
        'type' => 'text',
        'is_visible' => false,
    ],
    /*
    |--------------------------------------------------------------------------
    | Email Settings
    | Managed exclusively by SpecialSettingsController — hidden from the
    | general settings form (is_visible = false).
    |--------------------------------------------------------------------------
    */
    'email_mailers' => [
        'group' => 'General',
        'value' => '[{"TYPE":"log","VALUE":{"transport":"log","from":{"address":"noreply@example.com","name":"Platform"}}}]',
        'type' => 'json',
        'is_visible' => false,
    ],
    'email_mailer' => [
        'group' => 'General',
        'value' => 'log',
        'type' => 'select',
        'description' => 'Default mailer (e.g. log, smtp). Use "log" to write emails to the log instead of sending.',
        'is_visible' => false,
    ],
    /*
    |--------------------------------------------------------------------------
    | SMS Settings
    | Managed exclusively by SpecialSettingsController — hidden from the
    | general settings form (is_visible = false).
    |--------------------------------------------------------------------------
    */
    'sms_gateway' => [
        'group' => 'General',
        'value' => 'log',
        'type' => 'select',
        'description' => 'Default SMS gateway. Use "log" to write SMS to the log instead of sending.',
        'is_visible' => false,
    ],
    'sms_gateways' => [
        'group' => 'General',
        'value' => '[{"TYPE":"log","VALUE":{"endpoint":"local","method":"POST","mobile_prefix":null,"mobile_key":"mobile","message_key":"message"}}]',
        'type' => 'json',
        'is_visible' => false,
    ],
    /*
    |--------------------------------------------------------------------------
    | Privacy Policy Settings
    | Managed exclusively by SpecialSettingsController — hidden from the
    | general settings form (is_visible = false).
    |--------------------------------------------------------------------------
    */
    'privacy_policy' => [
        'group' => 'General',
        'value' => '<h1>Privacy Policy</h1><p>This is the privacy policy content for the application.</p>',
        'type' => 'textarea',
        'description' => 'The privacy policy content for the application',
        'is_visible' => false,
    ],
    /*
    |--------------------------------------------------------------------------
    | Terms & Conditions Settings
    | Managed exclusively by SpecialSettingsController — hidden from the
    | general settings form (is_visible = false).
    |--------------------------------------------------------------------------
    */
    'terms_conditions' => [
        'group' => 'General',
        'value' => '<h1>Terms & Conditions</h1><p>This is the terms and conditions content for the application.</p>',
        'type' => 'textarea',
        'description' => 'The terms and conditions content for the application',
        'is_visible' => false,
    ],
    /*
    |--------------------------------------------------------------------------
    | Firebase Settings
    | Managed exclusively by SpecialSettingsController — hidden from the
    | general settings form (is_visible = false).
    |--------------------------------------------------------------------------
    */
    'firebase_credentials_json' => [
        'group' => 'Firebase',
        'value' => '',
        'type' => 'text',
        'description' => 'The credentials JSON for the Firebase project',
        'is_visible' => false,
    ],
    'firebase_project_id' => [
        'group' => 'Firebase',
        'value' => '',
        'type' => 'text',
        'description' => 'The project ID for the Firebase project',
        'is_visible' => false,
    ],
    /*
    |--------------------------------------------------------------------------
    | Social Auth Settings
    | Managed exclusively by SpecialSettingsController — hidden from the
    | general settings form (is_visible = false).
    |--------------------------------------------------------------------------
    */
    'google_client_id' => [
        'group' => 'Social Auth',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'google_client_secret' => [
        'group' => 'Social Auth',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'google_redirect_uri' => [
        'group' => 'Social Auth',
        'value' => '/auth/google/callback',
        'type' => 'text',
        'is_visible' => false,
    ],
    'github_client_id' => [
        'group' => 'Social Auth',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'github_client_secret' => [
        'group' => 'Social Auth',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'github_redirect_uri' => [
        'group' => 'Social Auth',
        'value' => '/auth/github/callback',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_client_id' => [
        'group' => 'Social Auth',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_client_secret' => [
        'group' => 'Social Auth',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_redirect_uri' => [
        'group' => 'Social Auth',
        'value' => '/auth/apple/callback',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_team_id' => [
        'group' => 'Social Auth',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_key_id' => [
        'group' => 'Social Auth',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_key_file' => [
        'group' => 'Social Auth',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Settings
    | Managed exclusively by ThemeSettingsController — hidden from the
    | general settings form (is_visible = false).
    |--------------------------------------------------------------------------
    */
    'theme_layout' => [
        'group' => 'Theme',
        'value' => '1',
        'type' => 'select',
        'options' => json_encode(['1' => 'Layout 1', '2' => 'Layout 2', '3' => 'Layout 3']),
        'description' => 'The overall page layout structure (1, 2, 3, or 6)',
        'is_visible' => false,
    ],
    'theme_color_mode' => [
        'group' => 'Theme',
        'value' => 'light',
        'type' => 'select',
        'options' => json_encode(['light' => 'Light', 'dark' => 'Dark', 'auto' => 'Auto (System)']),
        'description' => 'Global color mode: light, dark, or follow system preference',
        'is_visible' => false,
    ],
    'theme_direction' => [
        'group' => 'Theme',
        'value' => 'ltr',
        'type' => 'select',
        'options' => json_encode(['ltr' => 'LTR (Left to Right)', 'rtl' => 'RTL (Right to Left)']),
        'description' => 'Text direction for the entire application',
        'is_visible' => false,
    ],
    'theme_color_palette' => [
        'group' => 'Theme',
        'value' => 'blue',
        'type' => 'select',
        'options' => json_encode([
            'blue' => 'Blue (Primary)',
            'slate' => 'Slate (Secondary)',
            'indigo' => 'Indigo',
            'purple' => 'Purple',
            'pink' => 'Pink',
            'red' => 'Red (Danger)',
            'orange' => 'Orange (Warning)',
            'yellow' => 'Yellow',
            'green' => 'Green (Success)',
            'teal' => 'Teal',
            'cyan' => 'Cyan (Info)',
        ]),
        'description' => 'Active color palette (see data-color-palette in foundation.css).',
        'is_visible' => false,
    ],
    'theme_sidebar_color' => [
        'group' => 'Theme',
        'value' => 'light',
        'type' => 'select',
        'options' => json_encode(['dark' => 'Dark', 'light' => 'Light', 'transparent' => 'Transparent']),
        'description' => 'Main sidebar background color scheme',
        'is_visible' => false,
    ],
    'theme_sidebar_type' => [
        'group' => 'Theme',
        'value' => 'default',
        'type' => 'select',
        'options' => json_encode(['default' => 'Default (Full Width)', 'mini' => 'Mini (Icon Only)', 'collapsed' => 'Collapsed']),
        'description' => 'Main sidebar display type',
        'is_visible' => false,
    ],
    'theme_navbar_color' => [
        'group' => 'Theme',
        'value' => 'light',
        'type' => 'select',
        'options' => json_encode(['dark' => 'Dark', 'light' => 'Light']),
        'description' => 'Top navbar color scheme',
        'is_visible' => false,
    ],
    'theme_navbar_bg' => [
        'group' => 'Theme',
        'value' => '',
        'type' => 'text',
        'description' => 'Optional Bootstrap bg-* utility class for navbar (e.g. bg-primary, bg-indigo-800). Leave empty to use default.',
        'is_visible' => false,
    ],
    'theme_font_family' => [
        'group' => 'Theme',
        'value' => 'inter',
        'type' => 'select',
        'options' => json_encode(['inter' => 'Inter', 'roboto' => 'Roboto', 'poppins' => 'Poppins', 'system' => 'System Default']),
        'description' => 'Global font family applied to the entire application',
        'is_visible' => false,
    ],
];
