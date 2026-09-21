<?php

namespace Modules\BackupCleanup\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Run the backup cleanup command via Artisan.
 *
 * Intended to be called from a queue worker (RunCleanupJob) or the CLI command,
 * both of which run in a console context where CSRF is not a concern.
 *
 * Usage:
 *   app(RunCleanupAction::class)->execute();
 */
final readonly class RunCleanupAction
{
    /**
     * @throws \RuntimeException
     */
    public function execute(): string
    {
        Artisan::call('backup:clean', [
            '--disable-notifications' => true,
        ]);

        $output = trim(Artisan::output());

        Log::info('Backup cleanup completed. Output: '.$output);

        return $output;
    }
}
