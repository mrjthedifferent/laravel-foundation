<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Providers\SettingsServiceProvider;

return new class extends Migration
{
    /**
     * Move all Mobile App and Contact settings off the generic settings form;
     * they are now managed exclusively by the dedicated Mobile App settings page.
     */
    public function up(): void
    {
        DB::table('settings')
            ->whereIn('group', ['Mobile App', 'Contact'])
            ->update(['is_visible' => false]);

        Cache::forget(SettingsServiceProvider::cacheKey());
    }

    /**
     * Restore visibility on the generic settings form.
     */
    public function down(): void
    {
        DB::table('settings')
            ->whereIn('group', ['Mobile App', 'Contact'])
            ->update(['is_visible' => true]);

        Cache::forget(SettingsServiceProvider::cacheKey());
    }
};
