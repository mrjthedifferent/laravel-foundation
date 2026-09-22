<?php

namespace Modules\ActivityLog\Policies;

use App\Models\User;

class SmsLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('View SMS Log');
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete SMS Log');
    }
}
