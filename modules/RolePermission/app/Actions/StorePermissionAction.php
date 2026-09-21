<?php

namespace Modules\RolePermission\Actions;

use Spatie\Permission\Models\Permission;

/**
 * Create a new permission.
 *
 * Usage:
 *   app(StorePermissionAction::class)->execute('Edit Post', 'Blog', 'Allows editing posts');
 */
final readonly class StorePermissionAction
{
    public function execute(string $name, string $moduleName, ?string $description = null): Permission
    {
        return Permission::create([
            'name' => $name,
            'module_name' => $moduleName,
            'guard_name' => 'web',
            'description' => $description,
        ]);
    }
}
