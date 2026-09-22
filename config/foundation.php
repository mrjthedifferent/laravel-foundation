<?php

declare(strict_types=1);

/*
| App\Models\User must extend Mrj\Foundation\Models\User. This is a
| requirement, not a setting: ~95 files across the package import
| App\Models\User directly (Laravel's own convention), so a config key
| naming a different class would silently be read once and then ignored
| everywhere else.
*/

return [

    /*
    | Require every polymorphic model to have a morph map alias. Modules register
    | their own aliases; a project adds its own with Relation::morphMap().
    */
    'enforce_morph_map' => true,

    /*
    | The Spatie Permission guard every role and permission this package
    | creates is stamped with. There is currently one permission guard for
    | both the web admin panel and the Sanctum-authenticated API — Sanctum
    | resolves the same App\Models\User, which defaults to this guard for
    | role/permission checks either way.
    */
    'guards' => [
        'web' => env('FOUNDATION_GUARD', 'web'),
    ],

    /*
    | The two role names RolePermissionDatabaseSeeder creates. Rename them here,
    | before first seeding; every role check reads through
    | Mrj\Foundation\Support\Roles.
    |
    | Super Admin is not a role: it is a flag on the user that passes every
    | permission check, set only by `php artisan foundation:super-admin`.
    */
    'roles' => [
        'admin' => env('FOUNDATION_ROLE_ADMIN', 'Admin'),
        'user' => env('FOUNDATION_ROLE_USER', 'User'),
    ],

    /*
    | Prepended to every cache key this package writes (settings, dashboard
    | widgets, OAuth/FCM tokens). Empty by default — set it if this package's
    | cache store is shared with something else whose keys might collide,
    | e.g. another instance of it in the same Redis database.
    */
    'cache' => [
        'prefix' => env('FOUNDATION_CACHE_PREFIX', ''),
    ],

    /*
    | The filesystem disk FileManagerService reads and writes: uploaded images
    | and documents, generated exports, everything under Storage::disk(...) in
    | this package. Must be a disk your project's config/filesystems.php defines.
    */
    'storage' => [
        'disk' => env('FOUNDATION_STORAGE_DISK', 'public'),
        'exports_path' => env('FOUNDATION_EXPORTS_PATH', 'exports'),
    ],

    /*
    | Date/time display formats used across the admin UI's detail pages,
    | exports and notifications (list views that show a compact, minute-only
    | timestamp for space are a separate, deliberate choice and stay as-is).
    */
    'formats' => [
        'date' => env('FOUNDATION_DATE_FORMAT', 'Y-m-d'),
        'datetime' => env('FOUNDATION_DATETIME_FORMAT', 'Y-m-d H:i:s'),
    ],

    /*
    | The single source for every paginated listing's page size: the fixed
    | picker options ("Show 10/25/50/100 rows"), the default when the request
    | supplies none, and the ceiling for endpoints that accept an arbitrary
    | per_page instead of picking from 'options'. See perPage() and
    | cappedPerPage() in src/Helpers/common.php.
    */
    'pagination' => [
        'default' => (int) env('FOUNDATION_PAGINATION_DEFAULT', 10),
        'max' => (int) env('FOUNDATION_PAGINATION_MAX', 100),
        'options' => [10, 25, 50, 100],
    ],

    /*
    | The web admin panel's URL prefix, domain and middleware, and the API's URL
    | prefix. Route NAMES (admin.users.index, ...) stay fixed regardless — only
    | change these if a consuming app already owns the prefix or needs the admin
    | panel on its own subdomain. Publish this file and edit values in place;
    | setting only 'foundation.routing.prefix' via a config() call in your own
    | provider (rather than publishing) drops the other keys here, because
    | mergeConfigFrom() merges one level deep.
    */
    'routing' => [
        'prefix' => env('FOUNDATION_ROUTE_PREFIX', 'admin'),
        'domain' => env('FOUNDATION_ROUTE_DOMAIN'),
        'middleware' => ['auth'],
        'api_prefix' => env('FOUNDATION_API_PREFIX', 'v1'),
        // The package registers admin.dashboard. Set false to define it yourself.
        'dashboard' => true,
    ],

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
    | Files created by the web server stay group-writable (deploy user + php-fpm).
    | Set to null to leave the process umask alone.
    */
    'umask' => 0002,

];
