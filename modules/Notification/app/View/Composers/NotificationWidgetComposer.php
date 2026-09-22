<?php

namespace Modules\Notification\View\Composers;

use Modules\Notification\Models\FirebaseToken;
use Modules\Notification\Models\Notification;
use Mrj\Foundation\Support\WidgetComposer;
use Override;

/**
 * Supplies the Notifications dashboard widget.
 *
 * This is an unscoped, application-wide total, so a single shared cache key
 * is safe. A widget that ever becomes user-scoped must gain a scope segment
 * in its key.
 */
final class NotificationWidgetComposer extends WidgetComposer
{
    #[Override]
    protected function permissions(): array
    {
        return ['View Notification'];
    }

    #[Override]
    protected function key(): string
    {
        return 'notification';
    }

    #[Override]
    protected function build(): array
    {
        return [
            'total' => Notification::query()->count(),
            // Must go through query(): the model also has an instance method named
            // unread(), which shadows the scope when called statically.
            'unread' => Notification::query()->unread()->count(),
            'push_subscribers' => FirebaseToken::query()->distinct('user_id')->count('user_id'),
        ];
    }
}
