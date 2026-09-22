<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Actions;

use App\Models\User;
use Modules\ActivityLog\Jobs\ActivityLogExportJob;
use Modules\ImportDownloadManager\Enum\ImportType;
use Mrj\Foundation\Contracts\ImportTracker;

final readonly class ExportActivityLogsAction
{
    public function __construct(
        private ImportTracker $importTracker,
    ) {}

    public function execute(User $user, array $filters): void
    {
        $importManagerId = $this->importTracker->start(
            $user,
            'Activity Logs Export',
            ImportType::Download,
        );

        ActivityLogExportJob::dispatch($importManagerId, $filters);
    }
}
