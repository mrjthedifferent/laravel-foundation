<?php

namespace Modules\ActivityLog\View\Composers;

use Illuminate\View\View;
use Modules\ActivityLog\Models\SmsLog;
use Mrj\Foundation\Services\Dashboard\DashboardCache;
use OwenIt\Auditing\Models\Audit;

/**
 * Supplies the Activity & Logs dashboard widget. The queries live here rather than in the
 * Blade partial so they can be cached and so the view stays free of data access.
 *
 * These are unscoped, application-wide totals, so a single shared cache key is safe.
 * A widget that ever becomes user-scoped must gain a scope segment in its key.
 */
final readonly class ActivityLogWidgetComposer
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
        if (! auth()->user()?->hasAnyPermission(['View Activity Log', 'View SMS Log'])) {
            return null;
        }

        return $this->cache->remember('widget:activitylog', fn (): array => [
            // A half-open range rather than whereDate(): `created_at` is a timestamp,
            // and wrapping it in a cast prevents the index on it from being used.
            'activities_today' => Audit::query()
                ->where('created_at', '>=', today())
                ->where('created_at', '<', today()->addDay())
                ->count(),
            'sms_logs' => SmsLog::query()->count(),
            'recent_audits' => Audit::query()
                ->select('event', 'auditable_type', 'created_at')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
