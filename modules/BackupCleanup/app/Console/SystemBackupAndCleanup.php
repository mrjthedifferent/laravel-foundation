<?php

namespace Modules\BackupCleanup\Console;

use Illuminate\Console\Command;
use Modules\BackupCleanup\Actions\RunBackupAction;
use Modules\BackupCleanup\Actions\RunCleanupAction;

class SystemBackupAndCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:backup:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database and clean up old backups';

    /**
     * Create a new command instance.
     */
    public function __construct(
        private readonly RunBackupAction $runBackupAction,
        private readonly RunCleanupAction $runCleanupAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->comment('Starting backup...');
            $backupOutput = $this->runBackupAction->execute();
            $this->info($backupOutput);
            $this->comment('Backup complete.');

            $this->comment('Cleaning up old backups...');
            $cleanOutput = $this->runCleanupAction->execute();
            $this->info($cleanOutput);
            $this->comment('Clean up complete.');
        } catch (\Exception $e) {
            $this->error('Operation failed: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
