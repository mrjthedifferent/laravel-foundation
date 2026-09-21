<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Modules\ActivityLog\Listeners\LogEmailSending;
use Modules\ActivityLog\Listeners\LogEmailSent;
use Override;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        MessageSending::class => [LogEmailSending::class],
        MessageSent::class => [LogEmailSent::class],
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
