<?php

declare(strict_types=1);

namespace Modules\RolePermission\Actions;

use Illuminate\Validation\ValidationException;
use Mrj\Foundation\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Sync a role's permissions.
 *
 * Replaces all currently assigned permissions with the given list.
 * Pass an empty array to revoke all permissions.
 *
 * With an actor, only the permissions the actor holds can be granted or
 * revoked (a Super Admin holds them all): asking for one the actor lacks is
 * refused, and those the role already has but the actor lacks are kept. So
 * *Assign Permission* cannot hand out, to the actor's own role or any other,
 * more than the actor already has. Without an actor (seeders, console) the
 * list is applied as given.
 *
 * Usage:
 *   app(AssignPermissionAction::class)->execute($role, ['Edit Post', 'View Post'], $request->user());
 *   app(AssignPermissionAction::class)->execute($role, []);
 *
 * @throws ValidationException when the actor asks for a permission it lacks
 */
final readonly class AssignPermissionAction
{
    /**
     * @param  list<string>  $permissions  Permission names to sync
     */
    public function execute(Role $role, array $permissions, ?User $actor = null): void
    {
        if ($actor === null || $actor->isSuperAdmin()) {
            $role->syncPermissions($permissions);

            return;
        }

        $current = $role->permissions()->pluck('name')->all();
        $held = $actor->getAllPermissions()->pluck('name')->all();

        $refused = array_values(array_diff($permissions, $held, $current));

        if ($refused !== []) {
            throw ValidationException::withMessages([
                'permissions' => __('rolepermission::rolepermission.validation.permission_not_held', [
                    'permissions' => implode(', ', $refused),
                ]),
            ]);
        }

        // Permissions the actor holds follow the request; the rest stay as they were.
        $kept = array_diff($current, $held);
        $granted = array_intersect($permissions, $held);

        $role->syncPermissions(array_values(array_unique([...$kept, ...$granted])));
    }
}
