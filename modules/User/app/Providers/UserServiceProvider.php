<?php

declare(strict_types=1);

namespace Modules\User\Providers;

use App\Models\User;
use Modules\User\Events\UserRolesChanged;
use Modules\User\Listeners\NotifyUserRolesChanged;
use Modules\User\Models\UserDocument;
use Modules\User\Models\UserLoginHistory;
use Modules\User\Policies\UserPolicy;
use Modules\User\Services\ImpersonationService;
use Modules\User\View\Composers\SignInsChart;
use Modules\User\View\Composers\UserStatComposer;
use Modules\User\View\Composers\UserWidgetComposer;
use Mrj\Foundation\Contracts\ImpersonationContext;
use Mrj\Foundation\Support\ModuleServiceProvider;
use Override;

class UserServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'User';

    protected string $nameLower = 'user';

    protected array $morphMap = [
        'user_login_history' => UserLoginHistory::class,
        'user_document' => UserDocument::class,
    ];

    protected array $policies = [
        User::class => UserPolicy::class,
    ];

    protected array $composers = [
        'user::partials.dashboard-widget' => UserWidgetComposer::class,
    ];

    protected array $dashboardStats = [
        UserStatComposer::class,
    ];

    protected array $dashboardCharts = [
        SignInsChart::class,
    ];

    protected array $listen = [
        UserRolesChanged::class => [NotifyUserRolesChanged::class],
    ];

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->singleton(ImpersonationContext::class, ImpersonationService::class);
    }

    #[Override]
    public function boot(): void
    {
        parent::boot();

        User::resolveRelationUsing('documents', fn (User $user) => $user->hasMany(UserDocument::class));
        User::resolveRelationUsing('loginHistory', fn (User $user) => $user->hasMany(UserLoginHistory::class));
        User::resolveRelationUsing('latestLogin', fn (User $user) => $user->hasOne(UserLoginHistory::class)->latestOfMany('logged_in_at'));
    }
}
