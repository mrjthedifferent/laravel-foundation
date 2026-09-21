<?php

namespace Modules\RolePermission\Actions;

use Spatie\Permission\Models\Role;

/**
 * Sync a role's permissions.
 *
 * Replaces all currently assigned permissions with the given list.
 * Pass an empty array to revoke all permissions.
 *
 * Usage:
 *   app(AssignPermissionAction::class)->execute($role, ['Edit Post', 'View Post']);
 *   app(AssignPermissionAction::class)->execute($role, []);
 */
final readonly class AssignPermissionAction
{
    /**
     * @param  list<string>  $permissions  Permission names to sync
     */
    public function execute(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);
    }
}
