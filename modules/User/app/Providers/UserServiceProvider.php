<?php

namespace Modules\User\Providers;

use App\Models\User;
use Modules\User\Models\UserDocument;
use Modules\User\Models\UserLoginHistory;
use Modules\User\Policies\UserPolicy;
use Modules\User\Services\ImpersonationService;
use Modules\User\View\Composers\UserWidgetComposer;
use Mrj\Foundation\Contracts\ImpersonationContext;
use Mrj\Foundation\Support\ModuleServiceProvider;

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

    public function register(): void
    {
        parent::register();

        $this->app->singleton(ImpersonationContext::class, ImpersonationService::class);
    }
}
