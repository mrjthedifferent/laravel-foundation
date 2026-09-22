<?php

use Mrj\Foundation\Contracts\SettingsRepository;

if (! function_exists('getSystemSetting')) {
    /**
     * Read one setting straight from the database. Prefer config('settings.{key}.value'),
     * which is already in memory; use this only where a stale cache is unacceptable.
     */
    function getSystemSetting($key)
    {
        return app(SettingsRepository::class)->fresh($key);
    }
}
