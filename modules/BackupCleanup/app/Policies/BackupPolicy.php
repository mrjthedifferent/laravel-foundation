<?php

namespace Modules\BackupCleanup\Policies;

use App\Models\User;

class BackupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('View Backup');
    }

    public function create(User $user): bool
    {
        return $user->can('Create Backup');
    }

    public function download(User $user): bool
    {
        return $user->can('Download Backup');
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete Backup');
    }

    public function cleanup(User $user): bool
    {
        return $user->can('Cleanup Backup');
    }
}
