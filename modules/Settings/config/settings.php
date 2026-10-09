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
    // No 'value': seeded from the current config (and .env) value.
    'date_format' => [
        'group' => 'General',
        'config' => 'foundation.formats.date',
        'type' => 'text',
        'description' => 'How dates are shown, in PHP date() format (e.g. Y-m-d, d/m/Y, d M Y)',
    ],
    'datetime_format' => [
        'group' => 'General',
        'config' => 'foundation.formats.datetime',
        'type' => 'text',
        'description' => 'How dates with a time are shown, in PHP date() format (e.g. Y-m-d H:i:s, d M Y h:i A)',
    ],
    'pagination_default' => [
        'group' => 'General',
        'config' => 'foundation.pagination.default',
        'type' => 'select',
        'options' => json_encode(['10' => '10', '25' => '25', '50' => '50', '100' => '100']),
        'description' => 'Rows shown per page in lists, until a user picks another size',
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
    // Managed on Settings → Security.
    'api_token_idle_expiration_minutes' => [
        'group' => 'Security',
        'config' => 'sanctum.idle_expiration',
        'value' => '43200',
        'type' => 'integer',
        'description' => 'Minutes of inactivity before a mobile/API login token expires. Each request the user makes slides this window forward, so a continuously-used token never expires. Default 43200 (30 days).',
        'is_visible' => false,
    ],

    // Managed on Settings → Security (SecuritySettingsController), which creates
    // these rows on its first save; until then the config (and .env) decides.
    'two_factor_enabled' => [
        'group' => 'Security',
        'config' => 'foundation.two_factor.enabled',
        'seed' => false,
        'type' => 'boolean',
        'description' => 'Let users turn on two-factor authentication from their profile',
        'is_visible' => false,
    ],
    'two_factor_required_for_super_admins' => [
        'group' => 'Security',
        'config' => 'foundation.two_factor.required_for_super_admins',
        'seed' => false,
        'type' => 'boolean',
        'description' => 'Super admins must set up two-factor authentication before using the panel',
        'is_visible' => false,
    ],
    'two_factor_required_roles' => [
        'group' => 'Security',
        'config' => 'foundation.two_factor.required_roles',
        'seed' => false,
        'type' => 'multi-select',
        'description' => 'Roles whose users must set up two-factor authentication before using the panel',
        'is_visible' => false,
    ],
    'two_factor_issuer' => [
        'group' => 'Security',
        'config' => 'foundation.two_factor.issuer',
        'seed' => false,
        'type' => 'text',
        'description' => 'Name shown in the authenticator app (the app name when empty)',
        'is_visible' => false,
    ],
    'password_min_length' => [
        'group' => 'Security',
        'config' => 'foundation.passwords.min_length',
        'seed' => false,
        'type' => 'integer',
        'description' => 'Minimum password length',
        'is_visible' => false,
    ],
    'password_mixed_case' => [
        'group' => 'Security',
        'config' => 'foundation.passwords.mixed_case',
        'seed' => false,
        'type' => 'boolean',
        'description' => 'Passwords need upper- and lower-case letters',
        'is_visible' => false,
    ],
    'password_numbers' => [
        'group' => 'Security',
        'config' => 'foundation.passwords.numbers',
        'seed' => false,
        'type' => 'boolean',
        'description' => 'Passwords need a number',
        'is_visible' => false,
    ],
    'password_symbols' => [
        'group' => 'Security',
        'config' => 'foundation.passwords.symbols',
        'seed' => false,
        'type' => 'boolean',
        'description' => 'Passwords need a symbol',
        'is_visible' => false,
    ],
    'password_uncompromised' => [
        'group' => 'Security',
        'config' => 'foundation.passwords.uncompromised',
        'seed' => false,
        'type' => 'boolean',
        'description' => 'Reject passwords found in known data breaches',
        'is_visible' => false,
    ],
    'login_max_attempts' => [
        'group' => 'Security',
        'config' => 'foundation.login.max_attempts',
        'seed' => false,
        'type' => 'integer',
        'description' => 'Failed sign-ins before a login is locked out for a minute',
        'is_visible' => false,
    ],
    'session_lifetime' => [
        'group' => 'Security',
        'config' => 'session.lifetime',
        'seed' => false,
        'type' => 'integer',
        'description' => 'Minutes of inactivity before a panel session ends',
        'is_visible' => false,
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
        'is_visible' => false,
    ],
    'app_tagline_mobile' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Tagline displayed below the app name on the splash and login screens',
        'is_visible' => false,
    ],
    'app_logo_mobile' => [
        'group' => 'Mobile App',
        'value' => null,
        'type' => 'image',
        'description' => 'Wide brand logo (splash, login hero)',
        'is_visible' => false,
    ],
    'app_icon' => [
        'group' => 'Mobile App',
        'value' => null,
        'type' => 'image',
        'description' => 'Square brand icon/badge',
        'is_visible' => false,
    ],
    'mobile_login_hero_image' => [
        'group' => 'Mobile App',
        'value' => null,
        'type' => 'image',
        'description' => 'Optional hero image shown on the login/splash screen',
        'is_visible' => false,
    ],

    // ── Theming ───────────────────────────────────────────────────────────
    'mobile_seed_color' => [
        'group' => 'Mobile App',
        'value' => '#2563EB',
        'type' => 'text',
        'description' => 'Primary seed color for the mobile app (hex, e.g. #2563EB). Drives the entire M3 color scheme — buttons, navigation, accents.',
        'is_visible' => false,
    ],
    'mobile_gradient_end_color' => [
        'group' => 'Mobile App',
        'value' => '#6D28D9',
        'type' => 'text',
        'description' => 'Brand gradient end color (hex, e.g. #6D28D9). Used in splash screen, login hero, and profile card stripe.',
        'is_visible' => false,
    ],
    'mobile_accent_color' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Optional tertiary accent color (hex). Leave blank to derive from the seed color.',
        'is_visible' => false,
    ],
    'mobile_default_theme_mode' => [
        'group' => 'Mobile App',
        'value' => 'system',
        'type' => 'select',
        'options' => json_encode(['system' => 'Follow System', 'light' => 'Light', 'dark' => 'Dark']),
        'description' => 'Default theme mode applied on first launch (before the user makes a choice)',
        'is_visible' => false,
    ],
    'mobile_allow_theme_toggle' => [
        'group' => 'Mobile App',
        'value' => '1',
        'type' => 'boolean',
        'description' => 'Allow users to switch theme mode (show the dark-mode control in app settings)',
        'is_visible' => false,
    ],
    'mobile_font_family' => [
        'group' => 'Mobile App',
        'value' => 'inter',
        'type' => 'select',
        'options' => json_encode(['inter' => 'Inter', 'roboto' => 'Roboto', 'poppins' => 'Poppins', 'system' => 'System Default']),
        'description' => 'Global font family for the mobile app',
        'is_visible' => false,
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
        'is_visible' => false,
    ],
    'mobile_default_language' => [
        'group' => 'Mobile App',
        'value' => 'en',
        'type' => 'select',
        'options' => json_encode(['en' => 'English', 'bn' => 'Bangla', 'ar' => 'Arabic', 'hi' => 'Hindi']),
        'description' => 'Default language applied on first launch',
        'is_visible' => false,
    ],
    'mobile_support_email' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Support email surfaced inside the app (falls back to Contact email)',
        'is_visible' => false,
    ],
    'google_maps_api_key_android' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Google Maps API key (Map Tiles API) served to Android clients; blank falls back to OpenStreetMap',
        'is_visible' => false,
    ],
    'google_maps_api_key_ios' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Google Maps API key (Map Tiles API) served to iOS clients; blank falls back to OpenStreetMap',
        'is_visible' => false,
    ],
    'google_maps_api_key_web' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Google Maps API key (Map Tiles API) served to web clients; blank falls back to OpenStreetMap',
        'is_visible' => false,
    ],

    // ── Store, Version & Maintenance ──────────────────────────────────────
    'app_version' => [
        'group' => 'Mobile App',
        'value' => '1.0.0',
        'type' => 'text',
        'description' => 'The version of the application',
        'is_visible' => false,
    ],
    'android_app_code' => [
        'group' => 'Mobile App',
        'value' => '1',
        'type' => 'integer',
        'description' => 'Current Android build number',
        'is_visible' => false,
    ],
    'android_app_code_min' => [
        'group' => 'Mobile App',
        'value' => '1',
        'type' => 'integer',
        'description' => 'Minimum supported Android build number',
        'is_visible' => false,
    ],
    'ios_app_code' => [
        'group' => 'Mobile App',
        'value' => '1',
        'type' => 'integer',
        'description' => 'Current iOS build number',
        'is_visible' => false,
    ],
    'ios_app_code_min' => [
        'group' => 'Mobile App',
        'value' => '1',
        'type' => 'integer',
        'description' => 'Minimum supported iOS build number',
        'is_visible' => false,
    ],
    'play_store_url' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Google Play Store listing URL shown on the public app download page',
        'is_visible' => false,
    ],
    'app_store_url' => [
        'group' => 'Mobile App',
        'value' => '',
        'type' => 'text',
        'description' => 'Apple App Store listing URL shown on the public app download page',
        'is_visible' => false,
    ],
    'mobile_apk_file' => [
        'group' => 'Mobile App',
        'value' => null,
        'type' => 'file',
        'description' => 'Direct Android APK download file for the public app download page',
        'is_visible' => false,
    ],
    'app_maintenance_mode' => [
        'group' => 'Mobile App',
        'value' => '0',
        'type' => 'boolean',
        'description' => 'Enable maintenance mode for the mobile application',
        'is_visible' => false,
    ],
    'app_maintenance_message' => [
        'group' => 'Mobile App',
        'value' => 'The app is temporarily down for maintenance. Please try again later.',
        'type' => 'textarea',
        'description' => 'Message shown in the mobile app while maintenance mode is on',
        'is_visible' => false,
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
        // Empty: the mail config in .env is used. Choosing a mailer here overrides it.
        'value' => '',
        'type' => 'select',
        'description' => 'Mailer to send with (e.g. smtp). Empty uses the mail settings in .env; "log" writes emails to the log instead of sending.',
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
        'config' => 'services.google.client_id',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'google_client_secret' => [
        'group' => 'Social Auth',
        'config' => 'services.google.client_secret',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'google_redirect_uri' => [
        'group' => 'Social Auth',
        'config' => 'services.google.redirect',
        'value' => '/auth/google/callback',
        'type' => 'text',
        'is_visible' => false,
    ],
    'github_client_id' => [
        'group' => 'Social Auth',
        'config' => 'services.github.client_id',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'github_client_secret' => [
        'group' => 'Social Auth',
        'config' => 'services.github.client_secret',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'github_redirect_uri' => [
        'group' => 'Social Auth',
        'config' => 'services.github.redirect',
        'value' => '/auth/github/callback',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_client_id' => [
        'group' => 'Social Auth',
        'config' => 'services.apple.client_id',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_client_secret' => [
        'group' => 'Social Auth',
        'config' => 'services.apple.client_secret',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_redirect_uri' => [
        'group' => 'Social Auth',
        'config' => 'services.apple.redirect',
        'value' => '/auth/apple/callback',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_team_id' => [
        'group' => 'Social Auth',
        'config' => 'services.apple.team_id',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_key_id' => [
        'group' => 'Social Auth',
        'config' => 'services.apple.key_id',
        'value' => '',
        'type' => 'text',
        'is_visible' => false,
    ],
    'apple_key_file' => [
        'group' => 'Social Auth',
        'config' => 'services.apple.key_file',
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
        'value' => 'indigo',
        'type' => 'select',
        'options' => json_encode([
            'indigo' => 'Indigo',
            'blue' => 'Blue',
            'violet' => 'Violet',
            'teal' => 'Teal',
            'green' => 'Green',
            'amber' => 'Amber',
            'rose' => 'Rose',
            'slate' => 'Slate',
            'custom' => 'Custom colour',
        ]),
        'description' => 'Accent palette (see data-color-palette in foundation.css).',
        'is_visible' => false,
    ],
    'theme_custom_color' => [
        'group' => 'Theme',
        'value' => '#4f46e5',
        'type' => 'text',
        'description' => 'Brand colour used when the palette is set to custom (hex, e.g. #4f46e5)',
        'is_visible' => false,
    ],
    'theme_sidebar_color' => [
        'group' => 'Theme',
        'value' => 'light',
        'type' => 'select',
        'options' => json_encode(['light' => 'Light', 'dark' => 'Dark']),
        'description' => 'Sidebar background: the light surface, or a dark panel',
        'is_visible' => false,
    ],
    'theme_sidebar_type' => [
        'group' => 'Theme',
        'value' => 'default',
        'type' => 'select',
        'options' => json_encode(['default' => 'Default (Full Width)', 'mini' => 'Mini (Icon Only)']),
        'description' => 'Main sidebar display type',
        'is_visible' => false,
    ],
    'theme_content_width' => [
        'group' => 'Theme',
        'value' => 'fluid',
        'type' => 'select',
        'options' => json_encode(['fluid' => 'Fluid (full width)', 'boxed' => 'Boxed (centered, max 1600px)']),
        'description' => 'How wide pages grow on large screens',
        'is_visible' => false,
    ],
    'theme_density' => [
        'group' => 'Theme',
        'value' => 'comfortable',
        'type' => 'select',
        'options' => json_encode(['comfortable' => 'Comfortable', 'compact' => 'Compact']),
        'description' => 'Spacing of controls, tables and cards',
        'is_visible' => false,
    ],
    'theme_radius' => [
        'group' => 'Theme',
        'value' => 'rounded',
        'type' => 'select',
        'options' => json_encode(['sharp' => 'Sharp', 'rounded' => 'Rounded', 'soft' => 'Soft']),
        'description' => 'Corner radius of controls and cards',
        'is_visible' => false,
    ],
];
