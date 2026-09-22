<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\User\Jobs\UserBulkUploadJob;
use Mrj\Foundation\Contracts\ImportTracker;

final readonly class BulkUploadUsersAction
{
    public function __construct(
        private ImportTracker $importTracker,
    ) {}

    public function execute(User $user, UploadedFile $file): void
    {
        $importManagerId = $this->importTracker->start(
            $user,
            'Users Upload',
            ImportType::Import,
            file: $file,
            directory: 'uploads/users',
        );

        UserBulkUploadJob::dispatch($importManagerId);
    }
}
