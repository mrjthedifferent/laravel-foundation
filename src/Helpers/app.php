<?php

declare(strict_types=1);

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
