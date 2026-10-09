<?php

declare(strict_types=1);

namespace Modules\ErrorReport\View\Composers;

use Modules\ErrorReport\Models\ErrorReport;
use Mrj\Foundation\Support\HealthCheck;
use Override;

/**
 * Open error reports: none is fine, a few need a look, a pile is a failure.
 */
final class ErrorReportHealth extends HealthCheck
{
    private const int PILE = 10;

    #[Override]
    public function permissions(): array
    {
        return ['View Error Report'];
    }

    #[Override]
    public function priority(): int
    {
        return 10;
    }

    #[Override]
    public function check(): array
    {
        $open = ErrorReport::query()->whereNull('resolved_at')->count();

        return [
            'status' => match (true) {
                $open === 0 => self::OK,
                $open < self::PILE => self::WARN,
                default => self::FAIL,
            },
            'label' => __('errorreport::errorreport.health.label'),
            'detail' => $open === 0
                ? __('errorreport::errorreport.health.none')
                : trans_choice('errorreport::errorreport.health.open', $open, ['count' => number_format($open)]),
            'href' => route('admin.error-reports.index'),
        ];
    }
}
