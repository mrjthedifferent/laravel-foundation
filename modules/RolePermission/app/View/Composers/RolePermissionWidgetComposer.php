<?php

declare(strict_types=1);

namespace Modules\RolePermission\View\Composers;

use Mrj\Foundation\Support\WidgetComposer;
use Override;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Supplies the Roles & Permissions dashboard widget.
 *
 * This is an unscoped, application-wide total, so a single shared cache key
 * is safe. A widget that ever becomes user-scoped must gain a scope segment
 * in its key.
 */
final class RolePermissionWidgetComposer extends WidgetComposer
{
    #[Override]
    protected function permissions(): array
    {
        return ['View Role', 'Assign Permission'];
    }

    #[Override]
    protected function key(): string
    {
        return 'rolepermission';
    }

    #[Override]
    protected function build(): array
    {
        return [
            'total_roles' => Role::query()->count(),
            'total_permissions' => Permission::query()->count(),
        ];
    }
}
