<?php

namespace Modules\User\Actions;

use App\Models\User;
use Modules\ImportDownloadManager\Actions\CreateImportRecordAction;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\User\Jobs\UserExportJob;

final readonly class ExportUsersAction
{
    public function __construct(
        private CreateImportRecordAction $createImportRecordAction,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(User $user, array $filters): void
    {
        $importManagerId = $this->createImportRecordAction->execute(
            $user,
            'Users Export',
            ImportType::Download,
        );

        UserExportJob::dispatch($importManagerId, $filters);
    }
}
