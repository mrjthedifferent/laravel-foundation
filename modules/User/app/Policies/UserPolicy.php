<?php

namespace Modules\User\Policies;

use App\Models\User;
use Modules\User\Services\ImpersonationService;

/**
 * User Policy
 *
 * Centralized authorization logic for user-related actions.
 * Replaces scattered authorization from middleware and Form Requests.
 *
 * SINGLE SOURCE OF TRUTH for all user permissions.
 */
class UserPolicy
{
    /**
     * Determine if user can view any users
     */
    public function viewAny(User $user): bool
    {
        return $user->can('View User');
    }

    /**
     * Determine if user can view a specific user
     */
    public function view(User $user, User $model): bool
    {
        // Can view if has permission OR viewing own profile
        return $user->can('View User') || $user->id === $model->id;
    }

    /**
     * Determine if user can create users
     *
     * Matches StoreUserRequest authorization logic:
     * - Must have 'Create User' permission
     * - Must have 'Assign Permission' to assign roles
     */
    public function create(User $user): bool
    {
        return $user->can('Create User') && $user->can('Assign Permission');
    }

    /**
     * Determine if user can update a specific user
     *
     * Matches UpdateUserRequest authorization logic:
     * - Must have 'Edit User' permission OR updating own profile
     */
    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return ! $this->shieldsSuperAdmin($user, $model) && $user->can('Edit User');
    }

    /**
     * Determine if user can change roles for a user
     *
     * Matches UpdateUserRequest role change logic
     */
    public function updateRoles(User $user): bool
    {
        return $user->can('Assign Permission');
    }

    /**
     * Determine if user can delete a specific user
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself, or a Super Admin (only the console removes one).
        if ($user->id === $model->id || $model->isSuperAdmin()) {
            return false;
        }

        return $user->can('Delete User');
    }

    /**
     * Determine if user can manually verify a user's email or phone
     */
    public function verifyContact(User $user, User $model): bool
    {
        return ! $this->shieldsSuperAdmin($user, $model) && $user->can('Verify User Contact');
    }

    /**
     * Determine if user can reset password for a user
     */
    public function resetPassword(User $user, User $model): bool
    {
        // Cannot reset own password through admin panel, nor a Super Admin's.
        if ($user->id === $model->id || $model->isSuperAdmin()) {
            return false;
        }

        return $user->can('User Password Reset');
    }

    /**
     * Determine if user can manage account (reset/delete)
     */
    public function manageAccount(User $user, User $model): bool
    {
        // Cannot manage own account, nor a Super Admin's.
        if ($user->id === $model->id || $model->isSuperAdmin()) {
            return false;
        }

        return $user->can('Delete User');
    }

    /**
     * Determine if user can upload documents for a user
     */
    public function uploadDocument(User $user, User $model): bool
    {
        // Can upload if has permission OR uploading to own profile
        return $user->id === $model->id || (! $this->shieldsSuperAdmin($user, $model) && $user->can('Edit User'));
    }

    /**
     * Determine if user can view documents of a user
     */
    public function viewDocuments(User $user, User $model): bool
    {
        // Can view if has permission OR viewing own documents
        return $user->can('View User') || $user->id === $model->id;
    }

    /**
     * Determine if user can export users
     */
    public function export(User $user): bool
    {
        return $user->can('View User');
    }

    /**
     * Determine if user can bulk upload users
     */
    public function bulkUpload(User $user): bool
    {
        return $user->can('Create User');
    }

    /**
     * Determine if user can update status of a user
     */
    public function updateStatus(User $user, User $model): bool
    {
        // Cannot change own status, nor a Super Admin's.
        if ($user->id === $model->id || $model->isSuperAdmin()) {
            return false;
        }

        return $user->can('Edit User');
    }

    /**
     * Determine if user can sign in as (impersonate) another user.
     *
     * Super Admin only — a flag, not a grantable permission. Super Admins
     * cannot be impersonated, nor can accounts that would be locked out on the
     * next request (inactive, or denied by the project's own rule).
     */
    public function impersonate(User $user, User $model): bool
    {
        if ($user->id === $model->id || ! $user->isSuperAdmin() || $model->isSuperAdmin()) {
            return false;
        }

        if (app(ImpersonationService::class)->isImpersonating()) {
            return false;
        }

        return $model->is_active === true && $model->accessDenialMessage() === null;
    }

    /**
     * A Super Admin account is out of reach of everyone who isn't one. (A Super
     * Admin actor never gets here for most abilities: Gate::before lets it through.)
     */
    private function shieldsSuperAdmin(User $user, User $model): bool
    {
        return $model->isSuperAdmin() && ! $user->isSuperAdmin();
    }

    /**
     * Determine if the current session can return to the impersonating Super Admin.
     */
    public function leaveImpersonation(User $user): bool
    {
        return app(ImpersonationService::class)->isImpersonating();
    }
}
