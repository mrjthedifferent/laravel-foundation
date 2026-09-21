<?php

namespace Modules\RolePermission\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('View Role');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('View Role');
    }

    public function create(User $user): bool
    {
        return $user->can('Create Role');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('Edit Role');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('Delete Role');
    }

    public function clone(User $user): bool
    {
        return $user->can('Create Role');
    }

    public function assignPermission(User $user, Role $role): bool
    {
        return $user->can('Assign Permission');
    }

    public function managePermissions(User $user): bool
    {
        return $user->can('Assign Permission');
    }
}
