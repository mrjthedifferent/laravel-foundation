<?php

declare(strict_types=1);

namespace Modules\User\View\Widgets;

use Illuminate\Contracts\View\View;
use Modules\User\Models\UserLoginHistory;
use Mrj\Foundation\Services\Dashboard\DashboardContext;
use Mrj\Foundation\Services\Dashboard\HourlySeries;
use Mrj\Foundation\Support\DashboardWidget;
use Override;

/**
 * When people sign in: successful sign-ins by weekday and hour over the chosen range.
 */
final class SignInHeatmapWidget extends DashboardWidget
{
    #[Override]
    public function key(): string
    {
        return 'sign-in-heatmap';
    }

    #[Override]
    public function title(): string
    {
        return __('user::user.widget.heatmap_title');
    }

    #[Override]
    public function icon(): string
    {
        return 'ph-grid-four';
    }

    #[Override]
    public function width(): int
    {
        return 8;
    }

    #[Override]
    public function order(): int
    {
        return 45;
    }

    #[Override]
    public function permissions(): array
    {
        return ['View User'];
    }

    #[Override]
    protected function cacheKey(DashboardContext $context): string
    {
        return $this->key().':'.$context->days;
    }

    #[Override]
    protected function data(DashboardContext $context): array
    {
        return ['matrix' => app(HourlySeries::class)->matrix(UserLoginHistory::query()->toBase(), 'logged_in_at', $context->days), 'days' => $context->days];
    }

    #[Override]
    protected function view(array $data, DashboardContext $context): View
    {
        return view('user::partials.widget-heatmap', $data);
    }
}
