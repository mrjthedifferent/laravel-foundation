<?php

namespace Mrj\Foundation\Support;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use Override;
use Symfony\Component\Finder\Finder;

/**
 * Base provider for a module, in the foundation or in a project. A module
 * declares what it contributes as properties; everything that used to be
 * hand-maintained in the app (morph map, policies, middleware) lives with the
 * module that owns it.
 *
 * The module's EventServiceProvider and RouteServiceProvider are registered
 * automatically when they exist beside the subclass.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    use PathNamespace;

    /** Module name as in module.json, e.g. 'RolePermission'. */
    protected string $name;

    /** Lowercase alias used for config, view and translation namespaces. */
    protected string $nameLower;

    /**
     * Morph map aliases for this module's polymorphic / audited models.
     * The alias is stored in the database: never change one once released.
     *
     * @var array<string, class-string>
     */
    protected array $morphMap = [];

    /** @var array<class-string, class-string> model => policy */
    protected array $policies = [];

    /** @var array<string, class-string> view name => composer */
    protected array $composers = [];

    /** @var list<class-string> */
    protected array $commands = [];

    /** @var array<string, class-string> */
    protected array $middlewareAliases = [];

    /** @var array<string, list<class-string>> group => middleware */
    protected array $prependToGroups = [];

    /** @var array<string, list<class-string>> group => middleware */
    protected array $appendToGroups = [];

    #[Override]
    public function register(): void
    {
        $namespace = substr(static::class, 0, (int) strrpos(static::class, '\\'));

        foreach (['EventServiceProvider', 'RouteServiceProvider'] as $provider) {
            if (class_exists($namespace.'\\'.$provider)) {
                $this->app->register($namespace.'\\'.$provider);
            }
        }
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->commands($this->commands);

        Relation::morphMap($this->morphMap);

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        foreach ($this->composers as $view => $composer) {
            View::composer($view, $composer);
        }

        $this->registerMiddleware();
    }

    /**
     * Group changes go through the HTTP kernel: the kernel re-syncs its groups to
     * the router whenever it is resolved, which would undo router-level changes.
     */
    protected function registerMiddleware(): void
    {
        foreach ($this->middlewareAliases as $alias => $middleware) {
            $this->app['router']->aliasMiddleware($alias, $middleware);
        }

        if ($this->prependToGroups === [] && $this->appendToGroups === []) {
            return;
        }

        $kernel = $this->app->make(Kernel::class);

        foreach ($this->prependToGroups as $group => $middlewares) {
            foreach ($middlewares as $middleware) {
                $kernel->prependMiddlewareToGroup($group, $middleware);
            }
        }

        foreach ($this->appendToGroups as $group => $middlewares) {
            foreach ($middlewares as $middleware) {
                $kernel->appendMiddlewareToGroup($group, $middleware);
            }
        }
    }

    /**
     * config/config.php becomes `{alias}`, every other file `{alias}.{file}`.
     * A project overrides one by publishing it to config/{alias}.php or
     * config/{alias}/{file}.php; Laravel loads those under the same keys.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, 'config');

        if (! is_dir($configPath)) {
            return;
        }

        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $isRoot = $relative === 'config.php';
            $key = $isRoot ? $this->nameLower : $this->nameLower.'.'.str_replace(['/', '.php'], ['.', ''], $relative);

            $this->publishes([
                $file->getPathname() => config_path($isRoot ? $this->nameLower.'.php' : $this->nameLower.'/'.$relative),
            ], [$this->nameLower.'-module-config', 'foundation-module-config']);

            $this->mergeModuleConfig($file->getPathname(), $key);
        }
    }

    /**
     * A project's list (menu, permissions) replaces the module's outright: merging
     * two lists would duplicate every entry. Keyed config merges, project values first.
     */
    protected function mergeModuleConfig(string $path, string $key): void
    {
        if ($this->app->configurationIsCached()) {
            return;
        }

        $existing = config($key);

        if (is_array($existing) && $existing !== [] && array_is_list($existing)) {
            return;
        }

        $this->mergeConfigFrom($path, $key);
    }

    protected function registerTranslations(): void
    {
        $projectPath = resource_path('lang/modules/'.$this->nameLower);
        $path = is_dir($projectPath) ? $projectPath : module_path($this->name, 'lang');

        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, $this->nameLower);
            $this->loadJsonTranslationsFrom($path);
        }
    }

    /**
     * A project overrides one module view by creating
     * resources/views/modules/{alias}/{same path}.
     */
    protected function registerViews(): void
    {
        $sourcePath = module_path($this->name, 'resources/views');

        if (! is_dir($sourcePath)) {
            return;
        }

        $paths = [];

        foreach (config('view.paths', []) as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        $this->publishes([
            $sourcePath => resource_path('views/modules/'.$this->nameLower),
        ], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($paths, [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(
            $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path'))),
            $this->nameLower,
        );
    }
}
