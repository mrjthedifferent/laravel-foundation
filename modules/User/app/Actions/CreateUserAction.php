<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Modules\User\Data\UserData;
use Modules\User\Events\UserCreated;
use Modules\User\Services\UserRoleService;

final readonly class CreateUserAction
{
    public function __construct(
        private UserRoleService $roleService,
    ) {}

    public function execute(UserData $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create user
            // The OwenIt Auditable observer automatically records the 'created' event
            $user = User::create([
                'email' => $data->email,
                'phone' => $data->phone,
                'password' => $data->password,
                'is_active' => $data->is_active,
                'name' => $data->name,
                'image' => $data->image,
                'gender' => $data->gender,
            ]);

            // Assign roles and audit the pivot attachment via the package's built-in auditAttach.
            // auditAttach passes directly to the Eloquent pivot, so Role models are required (not name strings).
            $roles = $this->roleService->getRoleModels($data->roles);
            $user->auditAttach('roles', $roles);

            event(new Registered($user));
            event(new UserCreated($user));

            return $user->fresh(['roles']);
        });
    }
}
