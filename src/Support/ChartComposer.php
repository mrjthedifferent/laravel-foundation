<?php

namespace Mrj\Foundation\Support;

use Mrj\Foundation\Services\Dashboard\DashboardCache;

/**
 * Base for the dashboard's chart. It follows {@see WidgetComposer}: the subclass
 * says who may see it, gives a cache key segment and returns a day => count
 * series; this class handles the permission check and the caching, and the
 * series is drawn by <x-chart-area>.
 *
 * The dashboard draws one chart — the registered composer with the lowest
 * priority the viewer may see — so a project can replace the package's by
 * registering its own with a lower number.
 *
 * @api
 */
abstract class ChartComposer
{
    public function __construct(protected DashboardCache $cache) {}

    /**
     * The series for the window, or null when the viewer may not see it.
     *
     * @return array<string, int>|null ['2026-09-10' => 4, …]
     */
    final public function series(int $days): ?array
    {
        if (! auth()->user()?->canAny($this->permissions())) {
            return null;
        }

        // The window is part of the key: 14 and 30 days are different answers.
        return $this->cache->remember(
            'chart:'.$this->key().':'.$days,
            fn (): array => $this->build($days),
        );
    }

    /**
     * What the chart counts, shown as the card's title.
     */
    abstract public function label(int $days): string;

    public function priority(): int
    {
        return 50;
    }

    /**
     * @return list<string>
     */
    abstract protected function permissions(): array;

    abstract protected function key(): string;

    /**
     * @return array<string, int> one entry per day, zeros included
     */
    abstract protected function build(int $days): array;
}
