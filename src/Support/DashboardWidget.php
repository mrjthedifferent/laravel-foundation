<?php

namespace Mrj\Foundation\Support;

use Illuminate\Contracts\View\View;
use Mrj\Foundation\Services\Dashboard\DashboardCache;
use Mrj\Foundation\Services\Dashboard\DashboardContext;

/**
 * Base for a card on the dashboard grid. A module lists its widgets in $dashboardWidgets
 * on its service provider; each viewer can then hide, reorder and resize them, and the
 * layout is saved per user.
 *
 * The subclass says who may see it and builds plain data (scalars and arrays only: a
 * Laravel 13 app ships cache.serializable_classes => false, so objects do not survive
 * a shared cache store); this class checks the permission and caches the data.
 * Return null from data() to show nothing at all, which also takes the card off the page.
 *
 * @api
 */
abstract class DashboardWidget
{
    /** The widths a viewer can pick, in twelfths of the row. */
    public const array WIDTHS = [3, 4, 6, 8, 12];

    public function __construct(protected DashboardCache $cache) {}

    /**
     * Stable identifier: it is stored in saved layouts, so never change it once released.
     */
    abstract public function key(): string;

    /**
     * The name shown in the layout editor.
     */
    abstract public function title(): string;

    public function icon(): string
    {
        return 'ph ph-squares-four';
    }

    /**
     * One of WIDTHS: the width a viewer gets before they choose their own.
     */
    public function width(): int
    {
        return 4;
    }

    /**
     * Where the widget sits for a viewer who has not arranged the page: lower comes first.
     */
    public function order(): int
    {
        return 50;
    }

    /**
     * Permissions that gate visibility, any one of them is enough. Empty means everyone.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * Whether the signed-in viewer may see this widget.
     */
    final public function allowed(): bool
    {
        $permissions = $this->permissions();

        return $permissions === [] || (bool) auth()->user()?->canAny($permissions);
    }

    /**
     * The card's HTML, or an empty string when there is nothing to show.
     */
    public function render(DashboardContext $context): string
    {
        if (! $this->allowed()) {
            return '';
        }

        $data = $this->load($context);

        if ($data === null) {
            return '';
        }

        return trim($this->view($data, $context)->render());
    }

    /**
     * The cache key segment for this render: the full key is "widget:{cacheKey}". A widget
     * whose data depends on the range lists it here.
     */
    protected function cacheKey(DashboardContext $context): string
    {
        return $this->key();
    }

    /**
     * The data for this render, from the cache when it has it. A widget that gathers
     * permission-dependent parts from sources that already cache themselves overrides
     * this to skip the shared cache entry, which would otherwise show one viewer's
     * parts to another.
     *
     * @return array<string, mixed>|null
     */
    protected function load(DashboardContext $context): ?array
    {
        $data = $this->cache->remember(
            'widget:'.$this->cacheKey($context),
            // The cache does not keep null, so "nothing to show" is stored as an empty array.
            fn (): array => $this->data($context) ?? [],
        );

        return $data === [] ? null : $data;
    }

    /**
     * The numbers the card needs. Unscoped, application-wide data only: the cache key is
     * shared between viewers, so anything per-user belongs in cacheKey().
     *
     * @return array<string, mixed>|null
     */
    abstract protected function data(DashboardContext $context): ?array;

    /**
     * @param  array<string, mixed>  $data
     */
    abstract protected function view(array $data, DashboardContext $context): View;
}
