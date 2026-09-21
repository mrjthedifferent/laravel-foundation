<?php

namespace Modules\Settings\View\Composers;

use Illuminate\View\View;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Services\Dashboard\DashboardCache;

/**
 * Supplies the Settings dashboard widget. The queries live here rather than in the
 * Blade partial so they can be cached and so the view stays free of data access.
 *
 * These are unscoped, application-wide totals, so a single shared cache key is safe.
 * A widget that ever becomes user-scoped must gain a scope segment in its key.
 */
final readonly class SettingsWidgetComposer
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
        if (! auth()->user()?->hasAnyPermission(['Edit System Setting', 'Edit Special Setting', 'Developer Setting'])) {
            return null;
        }

        return $this->cache->remember('widget:settings', fn (): array => [
            'total_settings' => Setting::query()->count(),
            'visible_settings' => Setting::query()->where('is_visible', true)->count(),
        ]);
    }
}
