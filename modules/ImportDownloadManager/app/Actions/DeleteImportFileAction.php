<?php

namespace Modules\ImportDownloadManager\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Modules\ImportDownloadManager\Support\ImportFileDisk;

/**
 * Delete the physical file associated with an import/download record.
 *
 * Usage:
 *   app(DeleteImportFileAction::class)->execute($record);
 */
final readonly class DeleteImportFileAction
{
    public function execute(DownloadImportManager $record): void
    {
        $disk = ImportFileDisk::forRecord($record);

        if ($disk !== null) {
            Storage::disk($disk)->delete($record->url);
        }
    }
}
