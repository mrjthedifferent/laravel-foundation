<?php

namespace Modules\ActivityLog\Providers;

use Illuminate\Support\Facades\Auth;
use Modules\ActivityLog\Http\Middleware\LogRequestResponse;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\ActivityLog\Models\EmailLog;
use Modules\ActivityLog\Models\SmsLog;
use Modules\ActivityLog\Policies\ActivityLogPolicy;
use Modules\ActivityLog\Policies\EmailLogPolicy;
use Modules\ActivityLog\Policies\SmsLogPolicy;
use Modules\ActivityLog\View\Composers\ActivityLogWidgetComposer;
use Mrj\Foundation\Support\ModuleServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use Override;

class ActivityLogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ActivityLog';

    protected string $nameLower = 'activitylog';

    protected array $policies = [
        ActivityLog::class => ActivityLogPolicy::class,
        SmsLog::class => SmsLogPolicy::class,
        EmailLog::class => EmailLogPolicy::class,
    ];

    protected array $composers = [
        'activitylog::partials.dashboard-widget' => ActivityLogWidgetComposer::class,
    ];

    protected array $prependToGroups = [
        'web' => [LogRequestResponse::class],
        'api' => [LogRequestResponse::class],
    ];

    #[Override]
    public function boot(): void
    {
        parent::boot();

        LogViewer::auth(function ($request) {
            return Auth::check() && Auth::user()->can('View Logs');
        });
    }
}
