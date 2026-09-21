<?php

namespace Modules\ActivityLog\Actions;

use App\Models\User;
use Modules\ActivityLog\Jobs\ActivityLogExportJob;
use Modules\ImportDownloadManager\Actions\CreateImportRecordAction;
use Modules\ImportDownloadManager\Enum\ImportType;

final readonly class ExportActivityLogsAction
{
    public function __construct(
        private CreateImportRecordAction $createImportRecordAction,
    ) {}

    public function execute(User $user, array $filters): void
    {
        $importManagerId = $this->createImportRecordAction->execute(
            $user,
            'Activity Logs Export',
            ImportType::Download,
        );

        ActivityLogExportJob::dispatch($importManagerId, $filters);
    }
}
