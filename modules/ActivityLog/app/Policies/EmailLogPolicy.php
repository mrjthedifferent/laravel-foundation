<?php

namespace Modules\ActivityLog\Policies;

use App\Models\User;

class EmailLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('View Email Log');
    }

    public function view(User $user): bool
    {
        return $user->can('View Email Log');
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete Email Log');
    }
}
