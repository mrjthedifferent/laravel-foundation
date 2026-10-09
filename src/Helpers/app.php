<?php

declare(strict_types=1);

use Mrj\Foundation\Foundation;

if (! function_exists('apiTokenIdleExpirationMinutes')) {
    /**
     * Minutes of inactivity before a Sanctum API token expires (sliding window):
     * config('sanctum.idle_expiration'), which the
     * 'api_token_idle_expiration_minutes' setting (Settings → Security)
     * overrides. 30 days when neither is set.
     */
    function apiTokenIdleExpirationMinutes(): int
    {
        return (int) config('sanctum.idle_expiration', 43200);
    }
}

if (! function_exists('appName')) {
    /**
     * The application's name as people see it: the app_name setting (a tenant's
     * own, with tenancy), falling back to config('app.name'). Use it anywhere
     * the name is shown: titles, footers, error pages, emails.
     */
    function appName(): string
    {
        return (string) (config('settings.app_name.value') ?: config('app.name', 'App'));
    }
}

if (! function_exists('foundation_asset')) {
    /**
     * URL of a file the foundation publishes to public/assets, with the package version
     * appended (`?v=…`). A browser then drops its cached copy when the package updates,
     * instead of mixing old and new files. The layouts load every foundation asset this way:
     * foundation_asset('assets/css/foundation.css').
     */
    function foundation_asset(string $path): string
    {
        return asset($path).'?v='.rawurlencode(Foundation::assetVersion());
    }
}
