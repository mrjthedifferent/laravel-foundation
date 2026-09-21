<?php

namespace Modules\BackupCleanup\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Delete a backup file from the configured storage disk.
 *
 * Usage:
 *   app(DeleteBackupAction::class)->execute('2024-01-01-120000.zip');
 */
final readonly class DeleteBackupAction
{
    /**
     * @throws \RuntimeException
     */
    public function execute(string $filename): void
    {
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupName = config('backup.backup.name', config('app.name'));
        $storageDisk = Storage::disk($disk);
        $filePath = $backupName.'/'.$filename;

        if (! $storageDisk->exists($filePath)) {
            throw new \RuntimeException('Backup file not found: '.$filePath);
        }

        $storageDisk->delete($filePath);

        Log::info('Backup file deleted: '.$filePath);
    }
}
