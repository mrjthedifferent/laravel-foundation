<?php

declare(strict_types=1);

namespace Modules\ErrorReport\View\Composers;

use Illuminate\Support\Carbon;
use Modules\ErrorReport\Models\ErrorReport;
use Mrj\Foundation\Support\StatComposer;
use Override;

/**
 * Unresolved error reports, as a dashboard headline stat — the module's only
 * dashboard contribution.
 *
 * An unscoped, application-wide total, so a single shared cache key is safe.
 */
final class ErrorReportStatComposer extends StatComposer
{
    #[Override]
    public function priority(): int
    {
        return 20;
    }

    #[Override]
    protected function permissions(): array
    {
        return ['View Error Report'];
    }

    #[Override]
    protected function key(): string
    {
        return 'error-report';
    }

    #[Override]
    protected function build(): array
    {
        $open = ErrorReport::query()->whereNull('resolved_at')->count();

        // A half-open range on the raw column, as everywhere else in the package.
        $thisWeek = ErrorReport::query()
            ->whereNull('resolved_at')
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->where('created_at', '<', Carbon::today()->addDay())
            ->count();

        return [[
            'label' => __('errorreport::errorreport.stat.open'),
            'value' => number_format($open),
            'icon' => 'ph-bug',
            'color' => $open > 0 ? 'danger' : 'success',
            'href' => route('admin.error-reports.index'),
            'caption' => __('errorreport::errorreport.stat.new_this_week', ['count' => number_format($thisWeek)]),
        ]];
    }
}
