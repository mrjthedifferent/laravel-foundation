<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Database\Seeders\UserDatabaseSeeder;
use RuntimeException;
use Tests\TestCase;

class UserDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_super_admin_in_local_without_a_configured_password(): void
    {
        config(['foundation.seed_admin.password' => null]);

        (new UserDatabaseSeeder)->run();

        $admin = User::where('email', config('foundation.seed_admin.email'))->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('Super Admin'));
        $this->assertTrue($admin->must_change_password);
    }

    public function test_it_refuses_to_seed_outside_local_without_a_configured_password(): void
    {
        config(['foundation.seed_admin.password' => null]);
        app()['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SEED_ADMIN_PASSWORD must be set before seeding outside a local or testing environment.');

        (new UserDatabaseSeeder)->run();
    }

    public function test_it_seeds_outside_local_when_a_password_is_configured(): void
    {
        config(['foundation.seed_admin.password' => 'a-real-secret']);
        app()['env'] = 'production';

        (new UserDatabaseSeeder)->run();

        $admin = User::where('email', config('foundation.seed_admin.email'))->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->must_change_password);
    }

    public function test_it_does_nothing_when_the_super_admin_already_exists(): void
    {
        User::factory()->create(['email' => config('foundation.seed_admin.email')]);

        (new UserDatabaseSeeder)->run();

        $this->assertSame(1, User::where('email', config('foundation.seed_admin.email'))->count());
    }
}
