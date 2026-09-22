<?php

namespace Modules\ActivityLog\View\Composers;

use Modules\ActivityLog\Models\SmsLog;
use Mrj\Foundation\Support\WidgetComposer;
use Override;
use OwenIt\Auditing\Models\Audit;

/**
 * Supplies the Activity & Logs dashboard widget.
 *
 * This is an unscoped, application-wide total, so a single shared cache key
 * is safe. A widget that ever becomes user-scoped must gain a scope segment
 * in its key.
 */
final class ActivityLogWidgetComposer extends WidgetComposer
{
    #[Override]
    protected function permissions(): array
    {
        return ['View Activity Log', 'View SMS Log'];
    }

    #[Override]
    protected function key(): string
    {
        return 'activitylog';
    }

    #[Override]
    protected function build(): array
    {
        return [
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
        ];
    }
}
