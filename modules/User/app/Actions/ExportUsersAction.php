<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use App\Models\User;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\User\Jobs\UserExportJob;
use Mrj\Foundation\Contracts\ImportTracker;

final readonly class ExportUsersAction
{
    public function __construct(
        private ImportTracker $importTracker,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(User $user, array $filters): void
    {
        $importManagerId = $this->importTracker->start(
            $user,
            __('user::user.flash.export_title'),
            ImportType::Download,
        );

        UserExportJob::dispatch($importManagerId, $filters);
    }
}
