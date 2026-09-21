<?php

namespace Modules\User\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Data\UserData;
use Spatie\Permission\Models\Role;

class UserDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = config('foundation.seed_admin');

        // Skip if the super admin already exists
        if (User::where('email', $admin['email'])->exists()) {
            return;
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        /** @var CreateUserAction $action */
        $action = app(CreateUserAction::class);

        $user = $action->execute(UserData::from([
            'name' => $admin['name'],
            'gender' => 'male',
            'email' => $admin['email'],
            'password' => $admin['password'],
            'password_confirmation' => $admin['password'],
            'is_active' => true,
            'roles' => [$superAdminRole->id],
        ]));

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();
    }
}
