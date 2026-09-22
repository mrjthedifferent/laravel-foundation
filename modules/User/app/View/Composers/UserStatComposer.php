<?php

declare(strict_types=1);

namespace Modules\User\View\Composers;

use App\Models\User;
use Illuminate\Support\Carbon;
use Modules\User\Models\UserLoginHistory;
use Mrj\Foundation\Support\StatComposer;
use Override;

/**
 * The two headline stats the User module puts on the dashboard: who can sign in,
 * and who did today.
 *
 * These are unscoped, application-wide totals, so a single shared cache key is
 * safe. A stat that ever becomes user-scoped must gain a scope segment in its key.
 */
final class UserStatComposer extends StatComposer
{
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
        return 'user';
    }

    #[Override]
    protected function build(): array
    {
        return [$this->activeUsers(), $this->signInsToday()];
    }

    /**
     * @return array<string, mixed>
     */
    private function activeUsers(): array
    {
        $thisWeek = $this->countBetween(User::query(), 'created_at', Carbon::today()->subDays(6), Carbon::today()->addDay());
        $lastWeek = $this->countBetween(User::query(), 'created_at', Carbon::today()->subDays(13), Carbon::today()->subDays(6));

        return [
            'label' => __('user::user.stat.active_users'),
            'value' => number_format(User::query()->where('is_active', true)->count()),
            'icon' => 'ph-users',
            'href' => route('admin.users.index'),
            'change' => $this->percentage($thisWeek, $lastWeek),
            'changeUp' => $thisWeek >= $lastWeek,
            'caption' => __('user::user.stat.vs_last_week'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function signInsToday(): array
    {
        $today = $this->countBetween(UserLoginHistory::query(), 'logged_in_at', Carbon::today(), Carbon::today()->addDay());
        $yesterday = $this->countBetween(UserLoginHistory::query(), 'logged_in_at', Carbon::yesterday(), Carbon::today());

        return [
            'label' => __('user::user.stat.sign_ins_today'),
            'value' => number_format($today),
            'icon' => 'ph-sign-in',
            'color' => 'info',
            'change' => $this->percentage($today, $yesterday),
            'changeUp' => $today >= $yesterday,
            'caption' => __('user::user.stat.vs_yesterday'),
        ];
    }

    /**
     * A half-open range rather than whereDate(): the column is a timestamp, and
     * wrapping it in a cast prevents the index on it from being used.
     */
    private function countBetween(mixed $query, string $column, Carbon $from, Carbon $to): int
    {
        return (int) $query->where($column, '>=', $from)->where($column, '<', $to)->count();
    }

    /**
     * The move from one period to the next. Growth from nothing has no percentage
     * to report, so it shows the count instead.
     */
    private function percentage(int $current, int $previous): ?string
    {
        if ($previous === 0) {
            return $current === 0 ? null : '+'.number_format($current);
        }

        $change = ($current - $previous) / $previous * 100;

        return number_format(abs($change), abs($change) < 10 ? 1 : 0).'%';
    }
}
