<?php

namespace Modules\Notification\Providers;

use Illuminate\Support\Facades\Notification as LaravelNotification;
use Modules\Notification\Channels\DatabaseChannel;
use Modules\Notification\Channels\FcmChannel;
use Modules\Notification\Channels\SmsChannel;
use Modules\Notification\Models\Notification;
use Modules\Notification\Models\PushNotification;
use Modules\Notification\Policies\NotificationPolicy;
use Modules\Notification\Policies\PushNotificationPolicy;
use Modules\Notification\View\Composers\NotificationWidgetComposer;
use Mrj\Foundation\Support\ModuleServiceProvider;
use Override;

class NotificationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Notification';

    protected string $nameLower = 'notification';

    protected array $morphMap = [
        'push_notification' => PushNotification::class,
        'notification' => Notification::class,
    ];

    protected array $policies = [
        Notification::class => NotificationPolicy::class,
        PushNotification::class => PushNotificationPolicy::class,
    ];

    protected array $composers = [
        'notification::partials.dashboard-widget' => NotificationWidgetComposer::class,
    ];

    #[Override]
    public function boot(): void
    {
        parent::boot();

        LaravelNotification::extend('database', fn ($app) => new DatabaseChannel);
        LaravelNotification::extend('sms', fn ($app) => new SmsChannel);
        LaravelNotification::extend('fcm', fn ($app) => new FcmChannel);
    }
}
