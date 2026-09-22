<?php

declare(strict_types=1);

namespace Modules\Settings\Listeners;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Events\SettingsUpdated;
use Throwable;

/**
 * Signals long-running queue workers to restart so they pick up settings
 * changes: SettingsServiceProvider copies settings into config() once, at
 * boot, so a worker that started before a setting was edited kept serving
 * the old value until the next deploy — a notification switched off in the
 * UI carried on sending from the queue. queue:restart just stamps a
 * timestamp workers check between jobs, so it is cheap and safe to call
 * repeatedly, but this listener still calls it at most once per process:
 * the notification settings page writes a row per notification per channel,
 * and restarting on each would boot the command hundreds of times in one
 * request.
 */
final class RestartQueueWorkers
{
    private static bool $signalled = false;

    public function handle(SettingsUpdated $event): void
    {
        if (self::$signalled) {
            return;
        }

        self::$signalled = true;

        try {
            Artisan::call('queue:restart');
        } catch (Throwable $e) {
            Log::warning('Settings saved but queue:restart failed; workers may serve stale settings.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
