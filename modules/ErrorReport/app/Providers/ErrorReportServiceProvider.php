<?php

namespace Modules\ErrorReport\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\ErrorReport\Models\ErrorReport;
use Modules\ErrorReport\Policies\ErrorReportPolicy;
use Modules\ErrorReport\Services\ErrorReporterService;
use Mrj\Foundation\Support\ModuleServiceProvider;

class ErrorReportServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ErrorReport';

    protected string $nameLower = 'errorreport';

    protected array $policies = [
        ErrorReport::class => ErrorReportPolicy::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::define('editErrorReportSettings', fn ($user) => $user->can('Edit Error Report Settings'));
    }

    public function register(): void
    {
        parent::register();

        // Foundation::exceptions() reports every exception to this binding when it exists.
        $this->app->singleton('error_reporter', fn ($app) => $app->make(ErrorReporterService::class));
    }
}
