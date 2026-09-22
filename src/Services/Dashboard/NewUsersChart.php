<?php

namespace Mrj\Foundation\Services\Dashboard;

use App\Models\User;
use Mrj\Foundation\Support\ChartComposer;
use Override;

/**
 * The chart the dashboard falls back to: new users per day, from the core users
 * table. The User module's sign-ins chart has a lower priority, so this only
 * draws where that module is disabled.
 *
 * @internal
 */
final class NewUsersChart extends ChartComposer
{
    #[Override]
    public function label(int $days): string
    {
        return __('foundation::foundation.dashboard.chart_new_users', ['days' => $days]);
    }

    #[Override]
    public function priority(): int
    {
        return 90;
    }

    #[Override]
    protected function permissions(): array
    {
        return ['View User'];
    }

    #[Override]
    protected function key(): string
    {
        return 'new-users';
    }

    #[Override]
    protected function build(int $days): array
    {
        return app(DailySeries::class)->count(User::query()->toBase(), 'created_at', $days);
    }
}
