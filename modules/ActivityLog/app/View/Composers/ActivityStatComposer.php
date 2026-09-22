<?php

namespace Modules\ActivityLog\View\Composers;

use Illuminate\Support\Carbon;
use Mrj\Foundation\Support\StatComposer;
use Override;
use OwenIt\Auditing\Models\Audit;

/**
 * How much happened in the application today, as a dashboard headline stat.
 *
 * An unscoped, application-wide total, so a single shared cache key is safe.
 */
final class ActivityStatComposer extends StatComposer
{
    #[Override]
    public function priority(): int
    {
        return 30;
    }

    #[Override]
    protected function permissions(): array
    {
        return ['View Activity Log'];
    }

    #[Override]
    protected function key(): string
    {
        return 'activity';
    }

    #[Override]
    protected function build(): array
    {
        // Half-open ranges rather than whereDate(): `created_at` is a timestamp,
        // and wrapping it in a cast prevents the index on it from being used.
        $today = Audit::query()
            ->where('created_at', '>=', Carbon::today())
            ->where('created_at', '<', Carbon::today()->addDay())
            ->count();

        $week = Audit::query()
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->where('created_at', '<', Carbon::today()->addDay())
            ->count();

        return [[
            'label' => __('activitylog::activitylog.stat.activity_today'),
            'value' => number_format($today),
            'icon' => 'ph-activity',
            'color' => 'success',
            'href' => route('admin.activity-logs.index'),
            'caption' => __('activitylog::activitylog.stat.this_week', ['count' => number_format($week)]),
        ]];
    }
}
