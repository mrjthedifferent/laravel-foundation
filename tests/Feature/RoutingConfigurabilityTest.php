<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Mrj\Foundation\Tests\TestCase;
use Override;
use Spatie\Permission\Models\Permission;

/**
 * foundation.routing.prefix/domain/api_prefix must actually change where the
 * routes live, while route NAMES stay fixed (admin.users.index, ...) — that
 * is the whole point of keeping names stable in Phase 4's design. Config
 * must be set before modules register their routes, so this overrides
 * resolveApplicationConfiguration() rather than setting config() in the test
 * body, which would run too late.
 */
class RoutingConfigurabilityTest extends TestCase
{
    #[Override]
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        // Full array, not a dot-notation partial: mergeConfigFrom() shallow-merges
        // at this level, so a partial 'routing' array here would silently drop
        // 'middleware' and 'domain' when the package's own defaults are merged in.
        $app['config']->set('foundation.routing', [
            'prefix' => 'backoffice',
            'domain' => null,
            'middleware' => ['auth'],
            'api_prefix' => 'api/v2',
        ]);
    }

    public function test_the_web_prefix_is_read_from_config_while_the_route_name_stays_fixed(): void
    {
        $this->assertTrue(Route::has('admin.users.index'));
        $this->assertSame('backoffice/users', Route::getRoutes()->getByName('admin.users.index')->uri());

        Permission::create(['name' => 'View User', 'guard_name' => 'web', 'module_name' => 'User']);
        $user = User::factory()->create();
        $user->givePermissionTo('View User');

        $this->actingAs($user)->get('/backoffice/users')->assertOk();
        $this->actingAs($user)->get('/admin/users')->assertNotFound();
    }

    public function test_the_api_prefix_is_read_from_config(): void
    {
        // The module's own routes/api.php sets the configurable prefix; Laravel's
        // module loader wraps every routes/api.php with its own outer 'api' prefix.
        $this->assertSame('api/api/v2/login', Route::getRoutes()->getByAction('Modules\User\Http\Controllers\Api\UserController@login')->uri());
    }
}
