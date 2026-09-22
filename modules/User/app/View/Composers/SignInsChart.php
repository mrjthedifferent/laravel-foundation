<?php

declare(strict_types=1);

namespace Modules\User\View\Composers;

use Modules\User\Models\UserLoginHistory;
use Mrj\Foundation\Services\Dashboard\DailySeries;
use Mrj\Foundation\Support\ChartComposer;
use Override;

/**
 * The dashboard's chart where this module is enabled: successful sign-ins per
 * day, one row per login from TrackLoginAction.
 */
final class SignInsChart extends ChartComposer
{
    #[Override]
    public function label(int $days): string
    {
        return __('user::user.stat.sign_ins_chart', ['days' => $days]);
    }

    #[Override]
    public function priority(): int
    {
        return 10;
    }

    #[Override]
    protected function permissions(): array
    {
        return ['View User'];
    }

    #[Override]
    protected function key(): string
    {
        return 'sign-ins';
    }

    #[Override]
    protected function build(int $days): array
    {
        return app(DailySeries::class)->count(UserLoginHistory::query()->toBase(), 'logged_in_at', $days);
    }
}
