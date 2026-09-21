<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Notifications\AppNotification;
use Modules\User\Data\UserData;
use Modules\User\Events\UserRolesChanged;
use Modules\User\Events\UserUpdated;
use Modules\User\Services\UserRoleService;

final readonly class UpdateUserAction
{
    public function __construct(
        private UserRoleService $roleService,
    ) {}

    public function execute(int $userId, UserData $data): User
    {
        return DB::transaction(function () use ($userId, $data) {
            $user = User::with(['roles'])->findOrFail($userId);

            $wasActive = (bool) $user->is_active;

            // Update user and profile fields
            // The OwenIt Auditable observer automatically records the changed attributes
            $user->update([
                'email' => $data->email ?? $user->email,
                'phone' => $data->phone ?? $user->phone,
                'is_active' => $data->is_active ?? $user->is_active,
                'name' => $data->name ?? $user->name,
                'image' => $data->image ?? $user->image,
                'gender' => $data->gender ?? $user->gender,
            ]);

            // Sync roles and audit pivot changes via the package's built-in auditSync.
            // null = not provided (skip); [] = explicitly clear all roles; [ids] = set roles
            $rolesChanged = false;
            if ($data->roles !== null) {
                $originalRoles = $this->roleService->getCurrentRoles($user);

                // auditSync passes directly to the Eloquent pivot, so Role models are required (not name strings).
                $roles = count($data->roles) > 0
                    ? $this->roleService->getRoleModels($data->roles)
                    : collect();

                // auditSync handles both the sync AND the pivot audit record in one call
                $user->auditSync('roles', $roles);

                $user = $user->fresh(['roles']);
                $newRoles = $this->roleService->getCurrentRoles($user);
                $rolesChanged = $this->roleService->rolesHaveChanged($originalRoles, $newRoles);

                if ($rolesChanged) {
                    event(new UserRolesChanged($user, $originalRoles, $newRoles));
                }
            } else {
                $user = $user->fresh(['roles']);
            }

            event(new UserUpdated($user, [
                'profile_updated' => true,
                'roles_changed' => $rolesChanged,
            ]));

            // Notify the user when their account has just been deactivated.
            if ($wasActive && ! $user->is_active) {
                DB::afterCommit(fn () => $user->notify(new AppNotification(
                    title: 'Account deactivated',
                    body: 'Your account has been deactivated. Contact an administrator if you believe this is a mistake.',
                    type: NotificationType::Info,
                    data: ['type' => 'account_deactivated'],
                    channels: ['database', 'fcm', 'mail'],
                )));
            }

            return $user;
        });
    }
}
