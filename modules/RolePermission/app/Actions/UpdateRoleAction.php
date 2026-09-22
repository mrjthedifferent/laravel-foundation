<?php

declare(strict_types=1);

namespace Modules\RolePermission\Actions;

use Spatie\Permission\Models\Role;

/**
 * Rename an existing role.
 *
 * Usage:
 *   app(UpdateRoleAction::class)->execute($role, 'New Name');
 */
final readonly class UpdateRoleAction
{
    public function execute(Role $role, string $name): Role
    {
        $role->update(['name' => $name]);

        return $role;
    }
}
