<?php

declare(strict_types=1);

namespace Modules\ImportDownloadManager\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\ImportDownloadManager\Actions\CreateImportRecordAction;
use Modules\ImportDownloadManager\Actions\UpdateImportRecordAction;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Mrj\Foundation\Contracts\ImportTracker;
use Override;

final readonly class EloquentImportTracker implements ImportTracker
{
    public function __construct(
        private CreateImportRecordAction $create,
        private UpdateImportRecordAction $update,
    ) {}

    #[Override]
    public function start(User $user, string $title, ImportType $type, ?UploadedFile $file = null, string $directory = 'uploads/imports'): int
    {
        return $this->create->execute($user, $title, $type, $file, $directory);
    }

    #[Override]
    public function filePath(int $id): ?string
    {
        return DownloadImportManager::findOrFail($id)->url;
    }

    #[Override]
    public function processing(int $id): void
    {
        $this->update->execute($id, ImportStatus::Processing);
    }

    #[Override]
    public function complete(int $id, ?string $remarks = null, ?string $url = null): void
    {
        $this->update->execute($id, ImportStatus::Completed, $remarks, $url);
    }

    #[Override]
    public function fail(int $id, ?string $remarks = null): void
    {
        $this->update->execute($id, ImportStatus::Failed, $remarks);
    }
}
