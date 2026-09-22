<?php

namespace Modules\ImportDownloadManager\Policies;

use App\Models\User;
use Modules\ImportDownloadManager\Models\DownloadImportManager;

class DownloadImportManagerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Download Import Manager Management');
    }

    public function statusUpdate(User $user): bool
    {
        return $user->can('Download Import Manager Management');
    }

    public function download(User $user, DownloadImportManager $record): bool
    {
        return $user->can('Import Manager Data Download');
    }

    public function delete(User $user, DownloadImportManager $record): bool
    {
        return $user->can('Import Manager Data Delete');
    }
}
