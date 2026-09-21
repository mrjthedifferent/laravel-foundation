<?php

namespace Modules\Notification\View\Composers;

use Illuminate\View\View;
use Modules\Notification\Models\FirebaseToken;
use Modules\Notification\Models\Notification;
use Mrj\Foundation\Services\Dashboard\DashboardCache;

/**
 * Supplies the Notifications dashboard widget. The queries live here rather than in the
 * Blade partial so they can be cached and so the view stays free of data access.
 *
 * These are unscoped, application-wide totals, so a single shared cache key is safe.
 * A widget that ever becomes user-scoped must gain a scope segment in its key.
 */
final readonly class NotificationWidgetComposer
{
    public function __construct(private DashboardCache $cache) {}

    public function compose(View $view): void
    {
        $view->with('widget', $this->data());
    }

    /**
     * @return array<string, mixed>|null null when the viewer may not see the widget
     */
    private function data(): ?array
    {
        if (! auth()->user()?->hasAnyPermission(['View Notification'])) {
            return null;
        }

        return $this->cache->remember('widget:notification', fn (): array => [
            'total' => Notification::query()->count(),
            // Must go through query(): the model also has an instance method named
            // unread(), which shadows the scope when called statically.
            'unread' => Notification::query()->unread()->count(),
            'push_subscribers' => FirebaseToken::query()->distinct('user_id')->count('user_id'),
        ]);
    }
}
