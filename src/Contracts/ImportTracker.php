<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\ImportDownloadManager\Enum\ImportType;

/**
 * Tracks an import/export job's lifecycle without the caller needing to know
 * about ImportDownloadManager's own Action classes or Eloquent model. Bound
 * in ImportDownloadManagerServiceProvider.
 *
 * ImportType is a plain value enum (Import|Download) with no behavior of its
 * own, so it stays a shared vocabulary type here rather than being
 * duplicated — the same treatment already given to Gender on the User model.
 */
interface ImportTracker
{
    public function start(User $user, string $title, ImportType $type, ?UploadedFile $file = null, string $directory = 'uploads/imports'): int;

    /**
     * The stored path of the file a record was created with (Import type
     * records only) — for a job that needs to read the uploaded file back.
     */
    public function filePath(int $id): ?string;

    public function processing(int $id): void;

    public function complete(int $id, ?string $remarks = null, ?string $url = null): void;

    public function fail(int $id, ?string $remarks = null): void;
}
