<?php

use Modules\Settings\Models\Setting;

if (! function_exists('getSystemSetting')) {
    /**
     * Read one setting straight from the database. Prefer config('settings.{key}.value'),
     * which is already in memory; use this only where a stale cache is unacceptable.
     */
    function getSystemSetting($key)
    {
        return Setting::where('key', $key)->first()->value ?? null;
    }
}
