<?php

namespace Modules\User\View\Composers;

use App\Models\User;
use Illuminate\View\View;
use Mrj\Foundation\Services\Dashboard\DashboardCache;

/**
 * Supplies the Users dashboard widget. The queries live here rather than in the
 * Blade partial so they can be cached and so the view stays free of data access.
 *
 * These are unscoped, application-wide totals, so a single shared cache key is safe.
 * A widget that ever becomes user-scoped must gain a scope segment in its key.
 */
final readonly class UserWidgetComposer
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
        if (! auth()->user()?->hasAnyPermission(['View User'])) {
            return null;
        }

        return $this->cache->remember('widget:user', fn (): array => [
            'total_users' => User::query()->count(),
            'active_users' => User::query()->where('is_active', true)->count(),
            // `created_at` is a timestamp, so use a half-open range, not whereDate().
            'new_today' => User::query()
                ->where('created_at', '>=', today())
                ->where('created_at', '<', today()->addDay())
                ->count(),
        ]);
    }
}
