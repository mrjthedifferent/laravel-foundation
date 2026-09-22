<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Artisan;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;
use Mrj\Foundation\Tests\TestCase;
use Spatie\Permission\Models\Permission;

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

        $this->admin = User::factory()->superAdmin()->create();
    }

    private User $admin;

    private function admin(): User
    {
        return $this->admin;
    }

    public function test_sync_permissions_reseeds_the_permissions_modules_declare(): void
    {
        Permission::where('name', 'View User')->delete();

        $this->actingAs($this->admin())
            ->post(route('admin.permission.sync'))
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');

        $this->assertDatabaseHas('permissions', ['name' => 'View User']);
    }

    public function test_every_artisan_command_the_modules_call_by_name_exists(): void
    {
        $commands = array_keys(Artisan::all());

        foreach (['backup:run', 'backup:clean', 'db:seed', 'queue:restart', 'system:backup:cleanup', 'clear:old-notification'] as $command) {
            $this->assertContains($command, $commands, "[$command] is called by a module but no package provides it");
        }
    }
}
