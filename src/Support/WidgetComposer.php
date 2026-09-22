<?php

namespace Mrj\Foundation\Support;

use Illuminate\View\View;
use Mrj\Foundation\Services\Dashboard\DashboardCache;

/**
 * Base for a module's dashboard widget composer. A subclass supplies the
 * permissions that gate visibility, the cache key segment, and the data to
 * cache; this class handles the permission check, the "return null when the
 * viewer can't see it" contract the dashboard partial expects, and wrapping
 * the query in DashboardCache so every widget shares one TTL/invalidation.
 *
 * @api
 */
abstract class WidgetComposer
{
    public function __construct(protected DashboardCache $cache) {}

    public function compose(View $view): void
    {
        $view->with('widget', $this->data());
    }

    /**
     * @return array<string, mixed>|null null when the viewer may not see the widget
     */
    private function data(): ?array
    {
        if (! auth()->user()?->hasAnyPermission($this->permissions())) {
            return null;
        }

        return $this->cache->remember('widget:'.$this->key(), fn (): array => $this->build());
    }

    /**
     * Permissions that gate visibility — any one of them is enough.
     *
     * @return list<string>
     */
    abstract protected function permissions(): array;

    /**
     * The cache key segment: the full key is "widget:{key()}".
     */
    abstract protected function key(): string;

    /**
     * The data to cache and hand to the widget's view: scalars and arrays only.
     * Laravel 13 apps ship `cache.serializable_classes => false`, so a model or
     * collection read back from a shared cache store is an incomplete object.
     *
     * @return array<string, mixed>
     */
    abstract protected function build(): array;
}
