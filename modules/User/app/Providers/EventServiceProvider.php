<?php

declare(strict_types=1);

namespace Modules\User\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\User\Events\UserRolesChanged;
use Modules\User\Listeners\NotifyUserRolesChanged;
use Override;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        UserRolesChanged::class => [
            NotifyUserRolesChanged::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    #[Override]
    protected function configureEmailVerification(): void
    {
        //
    }
}
