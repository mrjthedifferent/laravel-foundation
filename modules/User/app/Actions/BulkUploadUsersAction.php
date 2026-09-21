<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\ImportDownloadManager\Actions\CreateImportRecordAction;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\User\Jobs\UserBulkUploadJob;

final readonly class BulkUploadUsersAction
{
    public function __construct(
        private CreateImportRecordAction $createImportRecordAction,
    ) {}

    public function execute(User $user, UploadedFile $file): void
    {
        $importManagerId = $this->createImportRecordAction->execute(
            $user,
            'Users Upload',
            ImportType::Import,
            file: $file,
            directory: 'uploads/users',
        );

        UserBulkUploadJob::dispatch($importManagerId);
    }
}
