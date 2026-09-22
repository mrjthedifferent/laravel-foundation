<?php

namespace Mrj\Foundation\Tests;

use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\SanctumServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\FoundationServiceProvider;
use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Opcodes\LogViewer\LogViewerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use OwenIt\Auditing\AuditingServiceProvider;
use Spatie\Backup\BackupServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->applyFoundationMiddleware();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelModulesServiceProvider::class,
            PermissionServiceProvider::class,
            BackupServiceProvider::class,
            SanctumServiceProvider::class,
            AuditingServiceProvider::class,
            LaravelDataServiceProvider::class,
            SocialiteServiceProvider::class,
            LogViewerServiceProvider::class,
            FoundationServiceProvider::class,
        ];
    }

    /**
     * What a project's config files provide: the user model, and the modules
     * scanner pointed at the foundation's modules with all of them enabled. Set
     * here rather than in defineEnvironment(), which runs after the modules
     * package has already read its config and registered the module providers.
     */
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('audit.console', false);

        $app['config']->set('modules.scan', ['enabled' => true, 'paths' => [Foundation::modulesPath()]]);
        $app['config']->set('modules.activators', [
            'file' => [
                'class' => FileActivator::class,
                'statuses-file' => __DIR__.'/modules_statuses.json',
                'cache-key' => 'activator.installed',
                'cache-lifetime' => 604800,
            ],
        ]);
    }

    /**
     * A project route the API tests call. The dashboard comes from the package itself.
     */
    protected function defineRoutes($router): void
    {
        Route::middleware(['api', 'auth:sanctum'])->prefix('api')->group(function (): void {
            Route::get('/ping', fn () => ['pong' => true]);
        });
    }

    /**
     * Stands in for ->withMiddleware(Foundation::middleware()) in a project's bootstrap/app.php.
     */
    private function applyFoundationMiddleware(): void
    {
        $middleware = new Middleware;
        (Foundation::middleware())($middleware);

        $defaults = (new Middleware)->getMiddlewareGroups();
        $kernel = $this->app->make(Kernel::class);

        foreach ($middleware->getMiddlewareGroups() as $group => $members) {
            foreach (array_diff($members, $defaults[$group] ?? []) as $member) {
                $kernel->appendMiddlewareToGroup($group, $member);
            }
        }

        foreach ($middleware->getMiddlewareAliases() as $alias => $class) {
            $this->app['router']->aliasMiddleware($alias, $class);
        }
    }
}
