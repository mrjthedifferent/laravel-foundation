<?php

declare(strict_types=1);

namespace Modules\Settings\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Re-seed all settings from module config/settings.php files and flush the cache.
 *
 * Usage:
 *   app(SyncSettingsAction::class)->execute();
 */
final readonly class SyncSettingsAction
{
    public function execute(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'Modules\\Settings\\Database\\Seeders\\SettingsSettingsSeeder',
            '--force' => true,
        ]);

        Cache::forget('app_settings');
    }
}
