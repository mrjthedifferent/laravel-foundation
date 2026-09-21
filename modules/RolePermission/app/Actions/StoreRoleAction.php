<?php

namespace Modules\RolePermission\Actions;

use Spatie\Permission\Models\Role;

/**
 * Create a new role.
 *
 * Usage:
 *   app(StoreRoleAction::class)->execute('Editor');
 */
final readonly class StoreRoleAction
{
    public function execute(string $name): Role
    {
        return Role::create([
            'name' => $name,
            'guard_name' => config('foundation.guards.web'),
        ]);
    }
}
