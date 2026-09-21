<?php

namespace Modules\BackupCleanup\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Run a database backup via Artisan.
 *
 * Intended to be called from a queue worker (RunBackupJob) or the CLI command,
 * both of which run in a console context where CSRF is not a concern.
 *
 * Usage:
 *   app(RunBackupAction::class)->execute();
 */
final readonly class RunBackupAction
{
    /**
     * @throws \RuntimeException
     */
    public function execute(): string
    {
        Artisan::call('backup:run', [
            '--only-db' => true,
            '--disable-notifications' => true,
        ]);

        $output = trim(Artisan::output());

        Log::info('Backup completed. Output: '.$output);

        return $output;
    }
}
