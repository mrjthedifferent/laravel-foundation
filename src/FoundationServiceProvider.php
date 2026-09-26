<?php

namespace Mrj\Foundation;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LogicException;
use Mrj\Foundation\Console\InstallCommand;
use Mrj\Foundation\Console\MakeModuleCommand;
use Mrj\Foundation\Console\PublishCommand;
use Mrj\Foundation\Console\SuperAdminCommand;
use Mrj\Foundation\Console\SyncCommand;
use Mrj\Foundation\Contracts\ErrorReporter;
use Mrj\Foundation\Contracts\FileStorage;
use Mrj\Foundation\Contracts\ImpersonationContext;
use Mrj\Foundation\Contracts\OtpVerifier;
use Mrj\Foundation\Contracts\TenancyContext;
use Mrj\Foundation\Enums\ModuleContext;
use Mrj\Foundation\Events\TenancyContextChanged;
use Mrj\Foundation\Models\Audit;
use Mrj\Foundation\Models\User as FoundationUser;
use Mrj\Foundation\Services\Dashboard\ChartRegistry;
use Mrj\Foundation\Services\Dashboard\NewUsersChart;
use Mrj\Foundation\Services\Dashboard\StatRegistry;
use Mrj\Foundation\Services\LocalFileStorage;
use Mrj\Foundation\Support\ImpersonationAwareAuditUserResolver;
use Mrj\Foundation\Support\MigrationPaths;
use Mrj\Foundation\Support\NullErrorReporter;
use Mrj\Foundation\Support\NullImpersonationContext;
use Mrj\Foundation\Support\NullOtpVerifier;
use Mrj\Foundation\Support\NullTenancyContext;
use Mrj\Foundation\Support\Tenancy;
use Mrj\Foundation\View\Components\AppLayout;
use Mrj\Foundation\View\Components\ChartArea;
use Mrj\Foundation\View\Components\GuestLayout;
use Mrj\Foundation\View\Components\ModuleLayout;
use Mrj\Foundation\View\Components\StatusBadge;
use Mrj\Foundation\View\Composers\ThemeComposer;
use Override;
use Spatie\Permission\PermissionRegistrar;

/** @internal */
final class FoundationServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(Foundation::path('config/foundation.php'), 'foundation');
        $this->mergeConfigFrom(Foundation::path('config/sidebar.php'), 'sidebar');

        $this->app->singletonIf(ImpersonationContext::class, NullImpersonationContext::class);
        $this->app->singletonIf(ErrorReporter::class, NullErrorReporter::class);
        $this->app->singletonIf(FileStorage::class, LocalFileStorage::class);
        $this->app->singletonIf(OtpVerifier::class, NullOtpVerifier::class);
        $this->app->singletonIf(TenancyContext::class, NullTenancyContext::class);
        $this->app->singleton(MigrationPaths::class);

        // One registry per request: modules add their dashboard stats and charts as they boot.
        $this->app->singleton(StatRegistry::class);
        $this->app->singleton(ChartRegistry::class, function (): ChartRegistry {
            $registry = new ChartRegistry;
            // The fallback, drawn only where no module offers a better series.
            $registry->register(NewUsersChart::class);

            return $registry;
        });

        $this->configureAuditing();
        $this->configureLogChannels();
    }

    /**
     * The daily log channels the modules write to. A channel the project defines
     * in config/logging.php under the same name is left alone.
     */
    private function configureLogChannels(): void
    {
        foreach (config('foundation.log_channels', []) as $channel => $days) {
            if (config()->has("logging.channels.daily_$channel")) {
                continue;
            }

            config(["logging.channels.daily_$channel" => [
                'driver' => 'daily',
                'path' => storage_path("logs/$channel/$channel.log"),
                'level' => config('logging.channels.daily.level', 'debug'),
                'max_files' => $days,
                'replace_placeholders' => true,
            ]]);
        }
    }

    /**
     * Audits record the real actor during impersonation, accept API (Sanctum)
     * users, and skip noise columns. A project that publishes config/audit.php
     * takes over all of it.
     */
    private function configureAuditing(): void
    {
        if (file_exists($this->app->configPath('audit.php'))) {
            return;
        }

        config([
            'audit.implementation' => Audit::class,
            'audit.user.guards' => ['web', 'api', 'sanctum'],
            'audit.user.resolver' => ImpersonationAwareAuditUserResolver::class,
            'audit.empty_values' => false,
            'audit.exclude' => [
                'password',
                'password_confirmation',
                'remember_token',
                'email_verified_at',
                'last_login_at',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    public function boot(): void
    {
        $this->configureRuntime();
        $this->configureRateLimiting();
        $this->configureMorphMap();
        $this->configureSuperAdmin();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerRoutes();
        $this->listenForTenancyChanges();

        $this->loadMigrationsFrom(Foundation::path('database/migrations'));
        $this->app->make(MigrationPaths::class)->register(ModuleContext::Universal, Foundation::path('database/migrations'));

        $this->publishes([
            Foundation::path('config/foundation.php') => config_path('foundation.php'),
        ], 'foundation-config');

        $this->publishes([
            Foundation::path('config/sidebar.php') => config_path('sidebar.php'),
        ], 'foundation-sidebar');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, MakeModuleCommand::class, PublishCommand::class, SuperAdminCommand::class, SyncCommand::class]);
        }
    }

    private function configureRuntime(): void
    {
        // Set default string length for MariaDB compatibility
        Schema::defaultStringLength(191);

        // Throw on lazy loading everywhere except production, so an N+1 surfaces
        // in local/CI/staging as a hard error instead of a silent slow query that
        // only shows up under production load.
        Model::preventLazyLoading(! $this->app->isProduction());

        // The package ships its own Bootstrap 5 paginator rather than pointing at
        // one of Laravel's, whose view names change between majors. A project
        // overrides it with resources/views/pagination/links.blade.php.
        Paginator::defaultView('pagination.links');
        Paginator::defaultSimpleView('pagination.simple');

        if (config('foundation.umask') !== null) {
            umask((int) config('foundation.umask'));
        }

        if (config('foundation.force_https') === true) {
            URL::forceScheme('https');
        }

        $proxies = (string) config('foundation.trusted_proxies', '*');

        TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        TrustProxies::withHeaders(
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );
    }

    private function configureRateLimiting(): void
    {
        // Auth endpoints (login / forgot / reset). Keyed primarily by the
        // submitted credential so distinct users behind a shared NAT or carrier
        // IP don't collide, with a looser per-IP ceiling as a brute-force guard.
        RateLimiter::for('auth', function (Request $request) {
            $login = Str::lower(trim((string) $request->input('login', $request->input('email', ''))));

            return [
                Limit::perMinute(5)->by('auth:'.$login.'|'.$request->ip()),
                Limit::perMinute(30)->by('auth-ip:'.$request->ip()),
            ];
        });

        // Authenticated API traffic, per user; anonymous callers share a tighter per-IP bucket.
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by('api:'.$request->user()->getAuthIdentifier())
                : Limit::perMinute(30)->by('api-ip:'.$request->ip());
        });
    }

    /**
     * A Super Admin holds every permission without any role. Only permission checks
     * are bypassed — `can('Edit User')`, `@can('View Role')`, which take no
     * arguments. A policy check (`can('delete', $user)`) still runs its policy: its
     * permission checks pass, but its ownership and state rules still apply, so a
     * Super Admin cannot read someone else's notifications, delete itself, or
     * deactivate another Super Admin.
     */
    private function configureSuperAdmin(): void
    {
        Gate::before(function (mixed $user, string $ability, array $arguments): ?bool {
            return $arguments === [] && $user instanceof FoundationUser && $user->isSuperAdmin() ? true : null;
        });
    }

    private function configureMorphMap(): void
    {
        Relation::morphMap(['user' => User::class]);

        if (config('foundation.enforce_morph_map') === true) {
            Relation::requireMorphMap();
        }
    }

    /**
     * Shared UI chrome (layouts, error pages, form components) and the handful
     * of user-facing strings in src/ itself. A project overrides one string by
     * publishing this tag and editing resources/lang/vendor/foundation/en/foundation.php
     * — Laravel merges that file over the package's own automatically.
     */
    private function registerTranslations(): void
    {
        $this->loadTranslationsFrom(Foundation::path('lang'), 'foundation');

        $this->publishes([
            Foundation::path('lang') => $this->app->langPath('vendor/foundation'),
        ], 'foundation-lang');
    }

    /**
     * Only the dashboard: every other admin route belongs to a module. A config
     * published before the key existed has no 'dashboard' entry, which means on.
     */
    private function registerRoutes(): void
    {
        if ($this->app->routesAreCached() || config('foundation.routing.dashboard') === false) {
            return;
        }

        $middleware = Tenancy::enabled()
            ? ['web', ...(array) config('foundation.tenancy.middleware.universal', [])]
            : 'web';

        Route::middleware($middleware)->group(Foundation::path('routes/web.php'));
    }

    /**
     * A tenancy library's "tenant initialised" and "tenancy ended" events become
     * the one TenancyContextChanged event modules listen to. The permission cache
     * is re-keyed here: Spatie caches every role and permission under one key,
     * which would otherwise serve one tenant's roles to the next.
     */
    private function listenForTenancyChanges(): void
    {
        if (! Tenancy::enabled()) {
            return;
        }

        // nwidart would hand every enabled module's migrations to the central
        // migrator, tenant modules included. Module providers load their own
        // migrations, so its auto-discovery adds nothing but that mistake.
        if (config('modules.auto-discover.migrations', true) !== false) {
            throw new LogicException(
                "foundation.tenancy is enabled: set 'auto-discover' => ['migrations' => false] in config/modules.php, "
                .'or tenant-module tables are created in the central database.'
            );
        }

        foreach ((array) config('foundation.tenancy.context_changed_events', []) as $event) {
            Event::listen($event, fn () => TenancyContextChanged::dispatch());
        }

        Event::listen(TenancyContextChanged::class, function (): void {
            $registrar = $this->app->make(PermissionRegistrar::class);
            $registrar->cacheKey = config('permission.cache.key').'.'.(Tenancy::context()->tenantKey() ?? 'central');
            $registrar->clearPermissionsCollection();
        });
    }

    private function registerViews(): void
    {
        // Appended, so a file at the same path under the project's resources/views wins.
        $paths = config('view.paths', []);
        $paths[] = Foundation::uiPath('resources/views');
        config(['view.paths' => $paths]);
        View::addLocation(Foundation::uiPath('resources/views'));

        Blade::component('app-layout', AppLayout::class);
        Blade::component('guest-layout', GuestLayout::class);
        Blade::component('module-layout', ModuleLayout::class);
        Blade::component('status-badge', StatusBadge::class);
        Blade::component('chart-area', ChartArea::class);

        View::composer([
            'layouts.app',
            'layouts.guest',
            'errors.layout',
            'errors.minimal',
        ], ThemeComposer::class);
    }
}
