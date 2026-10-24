<?php

namespace Modules\User\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Service for handling user role management operations.
 * Separates role concerns from user CRUD operations (SRP principle).
 */
final readonly class UserRoleService
{
    /**
     * Get role names from role IDs.
     *
     * @param  array<int, int>  $roleIds  Array of role IDs
     * @return array<int, string> Array of role names
     */
    public function getRoleNames(array $roleIds): array
    {
        return Role::whereIn('id', $roleIds)
            ->pluck('name')
            ->toArray();
    }

    /**
     * Get Role models from role IDs.
     * Use this when passing to auditAttach/auditSync which operate directly
     * on the pivot and require model instances (not name strings).
     *
     * @param  array<int, int>  $roleIds  Array of role IDs
     * @return Collection<int, Role>
     */
    public function getRoleModels(array $roleIds): Collection
    {
        return Role::whereIn('id', $roleIds)->get();
    }

    /**
     * IDs of the roles an actor may assign: those whose permissions the actor
     * all holds. Null means every role (a Super Admin, or no actor: seeders and
     * console).
     *
     * @return array<int, int>|null
     */
    public function grantableRoleIds(?User $actor): ?array
    {
        if ($actor === null || $actor->isSuperAdmin()) {
            return null;
        }

        $held = $actor->getAllPermissions()->pluck('name')->all();

        return Role::whereDoesntHave('permissions', fn ($q) => $q->whereNotIn('name', $held))
            ->pluck('id')
            ->all();
    }

    /**
     * Roles for a role picker, as name by ID: those the actor may assign, plus
     * any listed in $keepIds (the roles a user already has).
     *
     * @param  array<int, int>  $keepIds
     * @return BaseCollection<int, string>
     */
    public function assignableRoleOptions(?User $actor, array $keepIds = []): BaseCollection
    {
        $grantable = $this->grantableRoleIds($actor);

        return Role::query()
            ->when($grantable !== null, fn ($q) => $q->whereIn('id', [...$grantable, ...$keepIds]))
            ->pluck('name', 'id');
    }

    /**
     * Refuse a role change that adds or removes a role whose permissions the
     * actor doesn't all hold, so no one can hand out (to themselves or anyone)
     * more than they have. Roles the user keeps are not checked.
     *
     * @param  array<int, int>  $roleIds  The roles the user will have
     * @param  array<int, int>  $currentRoleIds  The roles the user has now
     *
     * @throws ValidationException
     */
    public function assertActorCanAssign(?User $actor, array $roleIds, array $currentRoleIds = []): void
    {
        $grantable = $this->grantableRoleIds($actor);

        if ($grantable === null) {
            return;
        }

        $changed = array_merge(array_diff($roleIds, $currentRoleIds), array_diff($currentRoleIds, $roleIds));
        $refused = array_diff(array_map('intval', $changed), $grantable);

        if ($refused !== []) {
            throw ValidationException::withMessages([
                'roles' => __('user::user.errors.role_not_grantable', [
                    'roles' => Role::whereIn('id', $refused)->pluck('name')->implode(', '),
                ]),
            ]);
        }
    }

    /**
     * Assign roles to a user (additive operation).
     *
     * @param  User  $user  The user to assign roles to
     * @param  array<int, string>  $roleNames  Array of role names to assign
     */
    public function assignRoles(User $user, array $roleNames): void
    {
        $user->assignRole($roleNames);
    }

    /**
     * Sync roles for a user (replaces existing roles).
     *
     * @param  User  $user  The user to sync roles for
     * @param  array<int, string>  $roleNames  Array of role names to sync
     */
    public function syncRoles(User $user, array $roleNames): void
    {
        $user->syncRoles($roleNames);
    }

    /**
     * Get current role names for a user.
     *
     * @param  User  $user  The user to get roles for
     * @return array<int, string> Array of current role names
     */
    public function getCurrentRoles(User $user): array
    {
        return $user->roles->pluck('name')->toArray();
    }

    /**
     * Remove all roles from a user.
     *
     * @param  User  $user  The user
     */
    public function removeAllRoles(User $user): void
    {
        $user->syncRoles([]);
    }

    /**
     * Check if roles have changed.
     *
     * @param  array<int, string>  $originalRoles  Original role names
     * @param  array<int, string>  $newRoles  New role names
     */
    public function rolesHaveChanged(array $originalRoles, array $newRoles): bool
    {
        return array_diff($originalRoles, $newRoles) !== []
            || array_diff($newRoles, $originalRoles) !== [];
    }
}
