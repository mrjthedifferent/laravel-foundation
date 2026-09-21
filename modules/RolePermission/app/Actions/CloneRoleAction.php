<?php

namespace Modules\RolePermission\Actions;

use Spatie\Permission\Models\Role;

/**
 * Clone a role with all its permissions, generating a unique name.
 *
 * Usage:
 *   $clone = app(CloneRoleAction::class)->execute($role);
 */
final readonly class CloneRoleAction
{
    public function execute(Role $role): Role
    {
        $baseName = $role->name.' - Copy';
        $newName = $baseName;
        $counter = 1;

        while (Role::where('name', $newName)->exists()) {
            $counter++;
            $newName = $baseName.' '.$counter;
        }

        $clone = Role::create([
            'name' => $newName,
            'guard_name' => 'web',
        ]);

        $clone->syncPermissions($role->permissions()->pluck('name')->toArray());

        return $clone;
    }
}
