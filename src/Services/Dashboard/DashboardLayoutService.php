<?php

declare(strict_types=1);

namespace Mrj\Foundation\Services\Dashboard;

use Illuminate\Contracts\Auth\Authenticatable;
use Mrj\Foundation\Models\DashboardLayout;
use Mrj\Foundation\Support\DashboardWidget;

/**
 * Turns "what the viewer arranged" and "what is registered" into the list the page draws.
 *
 * A saved layout never has to be right about the present: a widget whose module was
 * disabled, or that the viewer lost permission for, simply is not in the registry any
 * more and is skipped; a widget added since is appended at its default place. So
 * enabling or disabling a module can never corrupt anyone's saved layout.
 *
 * @internal
 */
final readonly class DashboardLayoutService
{
    public function __construct(private WidgetRegistry $registry) {}

    /**
     * Every widget the viewer may see, arranged: key, width and hidden flag.
     *
     * @return list<array{key: string, width: int, hidden: bool}>
     */
    public function resolve(Authenticatable $user): array
    {
        $widgets = $this->registry->all();
        $saved = $this->saved($user);
        $resolved = [];

        foreach ($saved as $item) {
            if (! isset($widgets[$item['key']]) || isset($resolved[$item['key']])) {
                continue;
            }

            $resolved[$item['key']] = [
                'key' => $item['key'],
                'width' => $this->width($item['width'] ?? null, $widgets[$item['key']]),
                'hidden' => (bool) ($item['hidden'] ?? false),
            ];
        }

        foreach ($widgets as $key => $widget) {
            $resolved[$key] ??= ['key' => $key, 'width' => $widget->width(), 'hidden' => false];
        }

        return array_values($resolved);
    }

    /**
     * Stores an arrangement. Anything not a registered widget the viewer may see is
     * dropped, and a width that is not one of the offered ones falls back to the default.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array{key: string, width: int, hidden: bool}>
     */
    public function save(Authenticatable $user, array $items): array
    {
        $widgets = $this->registry->all();
        $clean = [];

        foreach ($items as $item) {
            $key = $item['key'] ?? null;

            if (! is_string($key) || ! isset($widgets[$key]) || isset($clean[$key])) {
                continue;
            }

            $clean[$key] = [
                'key' => $key,
                'width' => $this->width($item['width'] ?? null, $widgets[$key]),
                'hidden' => (bool) ($item['hidden'] ?? false),
            ];
        }

        $layout = array_values($clean);

        DashboardLayout::query()->updateOrCreate(['user_id' => $user->getAuthIdentifier()], ['layout' => $layout]);

        return $this->resolve($user);
    }

    /**
     * Back to the defaults.
     */
    public function reset(Authenticatable $user): void
    {
        DashboardLayout::query()->where('user_id', $user->getAuthIdentifier())->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function saved(Authenticatable $user): array
    {
        $layout = DashboardLayout::query()->where('user_id', $user->getAuthIdentifier())->value('layout');

        if (is_string($layout)) {
            $layout = json_decode($layout, true);
        }

        return is_array($layout) ? array_values(array_filter($layout, is_array(...))) : [];
    }

    private function width(mixed $asked, DashboardWidget $widget): int
    {
        return is_int($asked) && in_array($asked, DashboardWidget::WIDTHS, true) ? $asked : $widget->width();
    }
}
