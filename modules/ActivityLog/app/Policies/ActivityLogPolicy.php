<?php

namespace Modules\ActivityLog\Policies;

use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('View Activity Log');
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete Activity Log');
    }

    public function export(User $user): bool
    {
        return $user->can('Export Activity Log');
    }
}
