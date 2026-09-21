<?php

namespace Modules\ImportDownloadManager\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Mrj\Foundation\Services\FileManagerService;

/**
 * Create a new import/download manager record for a user.
 *
 * For Import type — pass the UploadedFile directly; the action uploads it
 * and stores the path on the record. The controller never touches file storage.
 *
 * For Download type — no file yet; the job sets the URL once the export is done.
 *
 * Usage:
 *   // Import (file upload)
 *   $id = app(CreateImportRecordAction::class)->execute($user, 'Users Upload', ImportType::Import, file: $request->file('users'));
 *
 *   // Download (export — no file yet)
 *   $id = app(CreateImportRecordAction::class)->execute($user, 'Users Export', ImportType::Download);
 */
final readonly class CreateImportRecordAction
{
    public function execute(
        User $user,
        string $title,
        ImportType $type,
        ?UploadedFile $file = null,
        string $directory = 'uploads/imports',
    ): int {
        $url = null;

        if ($file !== null) {
            $url = FileManagerService::uploadFile($file, directory: $directory);
        }

        $record = DownloadImportManager::create([
            'user_id' => $user->id,
            'title' => $title,
            'type' => $type,
            'status' => ImportStatus::Pending,
            'url' => $url,
        ]);

        return $record->id;
    }
}
