<?php

namespace Modules\User\View\Composers;

use App\Models\User;
use Mrj\Foundation\Support\WidgetComposer;
use Override;

/**
 * Supplies the Users dashboard widget.
 *
 * This is an unscoped, application-wide total, so a single shared cache key
 * is safe. A widget that ever becomes user-scoped must gain a scope segment
 * in its key.
 */
final class UserWidgetComposer extends WidgetComposer
{
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
        return [
            'total_users' => User::query()->count(),
            'active_users' => User::query()->where('is_active', true)->count(),
            // `created_at` is a timestamp, so use a half-open range, not whereDate().
            'new_today' => User::query()
                ->where('created_at', '>=', today())
                ->where('created_at', '<', today()->addDay())
                ->count(),
        ];
    }
}
