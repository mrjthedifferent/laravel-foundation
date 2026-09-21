<?php

declare(strict_types=1);

return [

    /*
    | The project's user model. It must extend Mrj\Foundation\Models\User.
    */
    'user_model' => 'App\\Models\\User',

    /*
    | Require every polymorphic model to have a morph map alias. Modules register
    | their own aliases; a project adds its own with Relation::morphMap().
    */
    'enforce_morph_map' => true,

    /*
    | Proxies whose X-Forwarded-* headers are trusted: '*', or a comma-separated
    | list of IPs / CIDRs. Behind a load balancer, without this every user shares
    | the proxy's IP and IP-keyed throttles collapse into one bucket.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES', '*'),

    'force_https' => (bool) env('FORCE_HTTPS', false),

    /*
    | `php artisan foundation:sync` keeps shared tooling files up to date. List the
    | targets a project maintains itself, for example ['pint.json', '.scripts/laravel.sh'].
    */
    'sync' => [
        'except' => [],
    ],

    /*
    | PDF exports (mPDF). `default_font` falls back to DejaVu Sans, which ships with
    | mPDF. To use your own, put the .ttf in resources/fonts and list it here:
    | 'fonts' => ['nikosh' => 'Nikosh.ttf'], 'default_font' => 'nikosh'
    */
    'pdf' => [
        'default_font' => env('PDF_DEFAULT_FONT', 'dejavusans'),
        'fonts' => [],
    ],

    /*
    | Daily log channels the modules write to (daily_api, daily_admin, ...) and
    | how many days each is kept: storage/logs/{name}/{name}.log
    */
    'log_channels' => [
        'api' => (int) env('LOG_API_DAILY_DAYS', 30),
        'admin' => (int) env('LOG_DAILY_DAYS', 365),
        'sms' => (int) env('LOG_DAILY_DAYS', 365),
        'email' => (int) env('LOG_DAILY_DAYS', 365),
        'notification' => (int) env('LOG_DAILY_DAYS', 365),
    ],

    /*
    | The first Super Admin, created by the User module's seeder when no account
    | with this email exists. SEED_ADMIN_PASSWORD is required outside local/testing
    | environments; the seeder aborts rather than fall back to a guessable password,
    | and forces a change on first sign-in regardless of environment.
    */
    'seed_admin' => [
        'name' => env('SEED_ADMIN_NAME', 'Super Admin'),
        'email' => env('SEED_ADMIN_EMAIL', 'superadmin@example.com'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],

    /*
    | Files created by the web server stay group-writable (deploy user + php-fpm).
    | Set to null to leave the process umask alone.
    */
    'umask' => 0002,

];
