<?php

namespace Modules\User\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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
