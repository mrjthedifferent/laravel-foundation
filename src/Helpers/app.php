<?php

use Spatie\Permission\Models\Permission;

if (! function_exists('allPermissions')) {

    function allPermissions()
    {
        return Permission::get();
    }
}
if (! function_exists('apiTokenIdleExpirationMinutes')) {
    /**
     * Minutes of inactivity before a Sanctum API token expires (sliding window).
     * Prefers the runtime 'api_token_idle_expiration_minutes' setting, falling
     * back to config('sanctum.idle_expiration') when the setting is unset (e.g.
     * before it is seeded, or during migrations).
     */
    function apiTokenIdleExpirationMinutes(): int
    {
        $value = config('settings.api_token_idle_expiration_minutes.value');

        return $value !== null
            ? (int) $value
            : (int) config('sanctum.idle_expiration', 43200);
    }
}
