<?php

namespace Modules\ErrorReport\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\ErrorReport\Models\ErrorReport;
use Modules\ErrorReport\Policies\ErrorReportPolicy;
use Modules\ErrorReport\Services\ErrorReporterService;
use Modules\ErrorReport\View\Composers\ErrorReportStatComposer;
use Mrj\Foundation\Contracts\ErrorReporter;
use Mrj\Foundation\Support\ModuleServiceProvider;
use Override;

class ErrorReportServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ErrorReport';

    protected string $nameLower = 'errorreport';

    protected array $dashboardStats = [
        ErrorReportStatComposer::class,
    ];

    protected array $policies = [
        ErrorReport::class => ErrorReportPolicy::class,
    ];

    #[Override]
    public function boot(): void
    {
        parent::boot();

        Gate::define('editErrorReportSettings', fn ($user) => $user->can('Edit Error Report Settings'));
    }

    #[Override]
    public function register(): void
    {
        parent::register();

        // Foundation::exceptions() reports every exception through this contract.
        $this->app->singleton(ErrorReporter::class, fn ($app) => $app->make(ErrorReporterService::class));
    }
}
