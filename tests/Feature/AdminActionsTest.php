<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Artisan;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;
use Mrj\Foundation\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Buttons that run artisan commands behind the scenes. They name a command or
 * a seeder as a string, so nothing fails until someone clicks them.
 */
class AdminActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);
        $this->seed(FoundationSeeder::class);

        // Not under test here; the forced-password-change flow is covered by
        // AdminSmokeTest and AccessTest.
        $this->admin()->forceFill(['must_change_password' => false])->save();
    }

    private function admin(): User
    {
        return User::where('email', config('foundation.seed_admin.email'))->sole();
    }

    public function test_sync_permissions_reseeds_them_and_gives_new_ones_to_super_admin(): void
    {
        Permission::where('name', 'View User')->delete();
        $this->assertFalse($this->admin()->fresh()->can('View User'));

        $this->actingAs($this->admin())
            ->post(route('admin.permission.sync'))
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');

        $this->assertDatabaseHas('permissions', ['name' => 'View User']);
        $this->assertTrue(Role::findByName('Super Admin')->hasPermissionTo('View User'));
        $this->assertTrue($this->admin()->fresh()->can('View User'));
    }

    public function test_every_artisan_command_the_modules_call_by_name_exists(): void
    {
        $commands = array_keys(Artisan::all());

        foreach (['backup:run', 'backup:clean', 'db:seed', 'queue:restart', 'system:backup:cleanup', 'clear:old-notification'] as $command) {
            $this->assertContains($command, $commands, "[$command] is called by a module but no package provides it");
        }
    }
}
