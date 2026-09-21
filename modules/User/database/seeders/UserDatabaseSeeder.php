<?php

namespace Modules\User\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Data\UserData;
use RuntimeException;
use Spatie\Permission\Models\Role;

class UserDatabaseSeeder extends Seeder
{
    /**
     * Local/testing-only fallback so a fresh clone can seed without any .env
     * setup. Never used outside those environments; see the guard below.
     */
    private const string LOCAL_DEV_PASSWORD = '12345678';

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

        $password = $admin['password'];

        if (blank($password)) {
            if (! app()->environment(['local', 'testing'])) {
                throw new RuntimeException(
                    'SEED_ADMIN_PASSWORD must be set before seeding outside a local or testing environment. '.
                    'Refusing to create the Super Admin with a guessable password.'
                );
            }

            $password = self::LOCAL_DEV_PASSWORD;
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        /** @var CreateUserAction $action */
        $action = app(CreateUserAction::class);

        $user = $action->execute(UserData::from([
            'name' => $admin['name'],
            'gender' => 'male',
            'email' => $admin['email'],
            'password' => $password,
            'password_confirmation' => $password,
            'is_active' => true,
            'roles' => [$superAdminRole->id],
        ]));

        $user->forceFill([
            'email_verified_at' => now(),
            'must_change_password' => true,
        ])->save();
    }
}
