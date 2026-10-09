<?php

declare(strict_types=1);

namespace Modules\ActivityLog\View\Widgets;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Mrj\Foundation\Services\Dashboard\DashboardContext;
use Mrj\Foundation\Support\DashboardWidget;
use Override;

/**
 * What kind of changes were made over the chosen range: audited records by event,
 * as a ranked bar list.
 */
final class ActivityByEventWidget extends DashboardWidget
{
    #[Override]
    public function key(): string
    {
        return 'activity-by-event';
    }

    #[Override]
    public function title(): string
    {
        return __('activitylog::activitylog.widget.by_event_title');
    }

    #[Override]
    public function icon(): string
    {
        return 'ph ph-chart-bar-horizontal';
    }

    #[Override]
    public function order(): int
    {
        return 50;
    }

    #[Override]
    public function permissions(): array
    {
        return ['View Activity Log'];
    }

    #[Override]
    protected function cacheKey(DashboardContext $context): string
    {
        return $this->key().':'.$context->days;
    }

    #[Override]
    protected function data(DashboardContext $context): ?array
    {
        $end = Carbon::today()->addDay();

        $counts = DB::table((string) config('audit.drivers.database.table', 'audits'))
            ->where('created_at', '>=', $end->copy()->subDays($context->days))
            ->where('created_at', '<', $end)
            ->selectRaw('event, count(*) as aggregate')
            ->groupBy('event')
            ->orderByDesc('aggregate')
            ->pluck('aggregate', 'event');

        if ($counts->isEmpty()) {
            return null;
        }

        $series = [];

        foreach ($counts as $event => $count) {
            $key = 'activitylog::activitylog.feed.event_'.$event;
            $series[Str::ucfirst(Lang::has($key) ? __($key) : Str::headline((string) $event))] = (int) $count;
        }

        return ['series' => $series, 'days' => $context->days];
    }

    #[Override]
    protected function view(array $data, DashboardContext $context): View
    {
        return view('activitylog::partials.widget-by-event', $data);
    }
}
