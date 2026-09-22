<?php

namespace Mrj\Foundation\Support;

use Mrj\Foundation\Services\Dashboard\DashboardCache;

/**
 * Base for a module's dashboard headline stat — the cards along the top of the
 * dashboard. It works exactly like {@see WidgetComposer}: the subclass supplies
 * the permissions that gate visibility, a cache key segment and the data, and
 * this class handles the permission check, the caching and the "contribute
 * nothing when the viewer can't see it" contract.
 *
 * A module registers one by listing it in $dashboardStats on its service
 * provider; each stat is rendered by <x-stat-card>.
 *
 * @api
 */
abstract class StatComposer
{
    public function __construct(protected DashboardCache $cache) {}

    /**
     * The stats this module contributes, ready for <x-stat-card>, or an empty
     * list when the viewer may not see them.
     *
     * @return list<array<string, mixed>>
     */
    final public function stats(): array
    {
        if (! auth()->user()?->canAny($this->permissions())) {
            return [];
        }

        return $this->cache->remember('stat:'.$this->key(), fn (): array => $this->build());
    }

    /**
     * Where this module's stats sit among everyone else's: lower comes first.
     */
    public function priority(): int
    {
        return 50;
    }

    /**
     * Permissions that gate visibility — any one of them is enough.
     *
     * @return list<string>
     */
    abstract protected function permissions(): array;

    /**
     * The cache key segment: the full key is "stat:{key()}".
     */
    abstract protected function key(): string;

    /**
     * The stats to cache, each a prop array for <x-stat-card>: label, value and
     * icon, plus optional color, href, change, changeUp and caption.
     *
     * Scalars and arrays only. Laravel 13 apps ship `cache.serializable_classes
     * => false`, so a model or collection read back from a shared cache store is
     * an incomplete object.
     *
     * @return list<array<string, mixed>>
     */
    abstract protected function build(): array;
}
