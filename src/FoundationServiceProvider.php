<?php

namespace Mrj\Foundation;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Mrj\Foundation\Console\InstallCommand;
use Mrj\Foundation\Console\MakeModuleCommand;
use Mrj\Foundation\Console\PublishCommand;
use Mrj\Foundation\Console\SyncCommand;
use Mrj\Foundation\Contracts\ImpersonationContext;
use Mrj\Foundation\Models\Audit;
use Mrj\Foundation\Support\ImpersonationAwareAuditUserResolver;
use Mrj\Foundation\Support\NullImpersonationContext;
use Mrj\Foundation\View\Components\AppLayout;
use Mrj\Foundation\View\Components\GuestLayout;
use Mrj\Foundation\View\Components\StatusBadge;
use Mrj\Foundation\View\Composers\ThemeComposer;
use Override;

class FoundationServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(Foundation::path('config/foundation.php'), 'foundation');

        $this->app->singletonIf(ImpersonationContext::class, NullImpersonationContext::class);

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
        $this->registerViews();

        $this->loadMigrationsFrom(Foundation::path('database/migrations'));

        $this->publishes([
            Foundation::path('config/foundation.php') => config_path('foundation.php'),
        ], 'foundation-config');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, MakeModuleCommand::class, PublishCommand::class, SyncCommand::class]);
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

    private function configureMorphMap(): void
    {
        Relation::morphMap(['user' => config('foundation.user_model')]);

        if (config('foundation.enforce_morph_map') === true) {
            Relation::requireMorphMap();
        }
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
        Blade::component('status-badge', StatusBadge::class);

        View::composer([
            'layouts.app',
            'layouts.guest',
            'errors.layout',
            'errors.minimal',
        ], ThemeComposer::class);
    }
}
