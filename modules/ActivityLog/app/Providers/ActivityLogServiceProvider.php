<?php

namespace Modules\ActivityLog\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Modules\ActivityLog\Http\Middleware\LogRequestResponse;
use Modules\ActivityLog\Listeners\LogEmailSending;
use Modules\ActivityLog\Listeners\LogEmailSent;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\ActivityLog\Models\Device;
use Modules\ActivityLog\Models\EmailLog;
use Modules\ActivityLog\Models\SmsLog;
use Modules\ActivityLog\Policies\ActivityLogPolicy;
use Modules\ActivityLog\Policies\EmailLogPolicy;
use Modules\ActivityLog\Policies\SmsLogPolicy;
use Modules\ActivityLog\View\Composers\ActivityFeedComposer;
use Modules\ActivityLog\View\Composers\ActivityStatComposer;
use Mrj\Foundation\Models\User;
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

    protected array $dashboardStats = [
        ActivityStatComposer::class,
    ];

    protected array $composers = [
        'activitylog::partials.dashboard-feed' => ActivityFeedComposer::class,
    ];

    protected array $prependToGroups = [
        'web' => [LogRequestResponse::class],
        'api' => [LogRequestResponse::class],
    ];

    protected array $listen = [
        MessageSending::class => [LogEmailSending::class],
        MessageSent::class => [LogEmailSent::class],
    ];

    #[Override]
    public function boot(): void
    {
        parent::boot();

        LogViewer::auth(function ($request) {
            return Auth::check() && Auth::user()->can('View Logs');
        });

        User::resolveRelationUsing('devices', fn (User $user) => $user->hasMany(Device::class));
    }
}
