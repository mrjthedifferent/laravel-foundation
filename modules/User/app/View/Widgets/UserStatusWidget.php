<?php

declare(strict_types=1);

namespace Modules\User\View\Widgets;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Mrj\Foundation\Services\Dashboard\DashboardContext;
use Mrj\Foundation\Support\DashboardWidget;
use Override;

/**
 * Accounts by state, as a donut: active, waiting on an email check, and switched off.
 * Unscoped, application-wide counts, so the shared cache key is safe.
 */
final class UserStatusWidget extends DashboardWidget
{
    #[Override]
    public function key(): string
    {
        return 'user-status';
    }

    #[Override]
    public function title(): string
    {
        return __('user::user.widget.status_title');
    }

    #[Override]
    public function icon(): string
    {
        return 'ph-chart-pie-slice';
    }

    #[Override]
    public function order(): int
    {
        return 40;
    }

    #[Override]
    public function permissions(): array
    {
        return ['View User'];
    }

    #[Override]
    protected function data(DashboardContext $context): ?array
    {
        $total = User::query()->count();

        if ($total === 0) {
            return null;
        }

        $inactive = User::query()->where('is_active', false)->count();
        $unverified = User::query()->where('is_active', true)->whereNull('email_verified_at')->count();

        return ['parts' => [
            ['label' => __('user::user.widget.status_active'), 'value' => $total - $inactive - $unverified, 'tone' => 'success'],
            ['label' => __('user::user.widget.status_unverified'), 'value' => $unverified, 'tone' => 'warning'],
            ['label' => __('user::user.widget.status_inactive'), 'value' => $inactive, 'tone' => 'muted'],
        ]];
    }

    #[Override]
    protected function view(array $data, DashboardContext $context): View
    {
        return view('user::partials.widget-status', $data);
    }
}
