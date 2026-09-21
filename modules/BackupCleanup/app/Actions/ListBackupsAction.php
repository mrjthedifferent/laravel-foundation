<?php

namespace Modules\BackupCleanup\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * List all available backup files from the configured storage disk.
 *
 * Usage:
 *   app(ListBackupsAction::class)->execute();
 */
final readonly class ListBackupsAction
{
    /**
     * Returns a collection of backup file metadata arrays, sorted newest first.
     *
     * Each item contains:
     *  - path     (string)  Full path on the disk
     *  - filename (string)  Basename of the file
     *  - size     (int)     Size in bytes
     *  - date     (int)     Last-modified Unix timestamp
     *  - disk     (string)  Storage disk name
     */
    public function execute(): Collection
    {
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupName = config('backup.backup.name', config('app.name'));
        $storageDisk = Storage::disk($disk);

        return collect($storageDisk->files($backupName))
            ->filter(fn (string $file) => str_ends_with($file, '.zip'))
            ->map(fn (string $file) => [
                'path' => $file,
                'filename' => basename($file),
                'size' => $storageDisk->size($file),
                'date' => $storageDisk->lastModified($file),
                'disk' => $disk,
            ])
            ->sortByDesc('date')
            ->values();
    }
}
