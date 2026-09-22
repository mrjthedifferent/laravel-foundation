<?php

namespace Modules\ImportDownloadManager\Support;

use Illuminate\Support\Facades\Storage;
use Modules\ImportDownloadManager\Models\DownloadImportManager;

/**
 * Which disk a record's file is on. Generated exports are private (served only
 * through the authorized download route); uploaded import files, and exports
 * written before that change, are on the public disk.
 */
final readonly class ImportFileDisk
{
    /** @var list<string> */
    public const array DISKS = ['local', 'public'];

    public static function forRecord(DownloadImportManager $record): ?string
    {
        if (blank($record->url)) {
            return null;
        }

        foreach (self::DISKS as $disk) {
            if (Storage::disk($disk)->exists($record->url)) {
                return $disk;
            }
        }

        return null;
    }
}
