<?php

namespace Modules\RolePermission\Actions;

use Spatie\Permission\Models\Permission;

/**
 * Delete a permission and revoke it from all roles first.
 *
 * Usage:
 *   app(DeletePermissionAction::class)->execute($permission);
 */
final readonly class DeletePermissionAction
{
    public function execute(Permission $permission): void
    {
        // detach from all roles before deletion
        $permission->roles()->detach();

        $permission->delete();
    }
}
