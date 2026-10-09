<?php

namespace Mrj\Foundation\Support;

use Illuminate\Support\Facades\Route;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Models\User;
use Nwidart\Modules\Facades\Module;

/**
 * The sidebar for one user: parents → pages, two levels.
 *
 * Every enabled module declares its pages in config/menu.php, naming the parent they
 * belong to; config/sidebar.php fixes the order of parents. A page is shown only when
 * its route exists (so a disabled module contributes nothing) and when the user holds
 * one of its permissions. With foundation.tenancy enabled, only modules that belong
 * where the app is now (central or tenant) contribute. A project can add its own rule for items carrying custom
 * flags through Foundation::sidebarVisibility().
 *
 * @phpstan-type Item array{group: string, label: string, icon: string, route?: string, url?: string, target?: string, routes?: list<string>, permissions?: list<string>, order?: int}
 *
 * @internal
 */
final readonly class SidebarMenu
{
    /**
     * An item is active when the current route is its `route` or one of its `routes`. When none
     * is, an item declared by `url` is active if the current page is that URL or sits under it
     * (`admin/things` covers `admin/things/5/edit`); the longest such URL wins. That covers pages
     * that share one route name and differ only by its parameters.
     *
     * @param  string|null  $currentPath  the request path; defaults to the current request's
     * @return list<array{key: string, label: string, icon: string, single: bool, open: bool, items: list<array{label: string, icon: string, href: string, target: ?string, active: bool}>}>
     */
    public function forUser(?User $user, ?string $currentRoute, ?string $currentPath = null): array
    {
        if ($user === null) {
            return [];
        }

        $tree = $this->tree($user, $currentRoute);

        return $this->markUrlMatch($tree, $currentPath ?? request()->path());
    }

    /**
     * @return list<array{key: string, label: string, icon: string, single: bool, open: bool, items: list<array{label: string, icon: string, href: string, target: ?string, active: bool}>}>
     */
    private function tree(User $user, ?string $currentRoute): array
    {

        $byParent = [];

        foreach ($this->declaredItems() as $item) {
            if ($this->visible($item, $user)) {
                $byParent[$item['group']][] = $item;
            }
        }

        $tree = [];

        foreach (config('sidebar.groups', []) as $key => $parent) {
            if (empty($byParent[$key])) {
                continue;
            }

            $items = $byParent[$key];
            usort($items, fn (array $a, array $b) => ($a['order'] ?? 100) <=> ($b['order'] ?? 100));

            $rendered = [];
            $open = false;

            foreach ($items as $item) {
                $active = $currentRoute !== null && in_array($currentRoute, $this->activeRoutes($item), true);
                $open = $open || $active;

                // Labels are English source strings from config; a project translates
                // them with a lang/{locale}.json file keyed by that English text.
                $rendered[] = [
                    'label' => display_label($item['label']),
                    'icon' => $item['icon'],
                    'href' => isset($item['route']) ? route($item['route']) : url((string) $item['url']),
                    'target' => $item['target'] ?? null,
                    'active' => $active,
                ];
            }

            $tree[] = [
                'key' => $key,
                'label' => display_label($parent['label']),
                'icon' => $parent['icon'],
                // A single-page parent is a plain link, not a submenu of one.
                'single' => (bool) ($parent['single'] ?? false),
                'open' => $open,
                'items' => $rendered,
            ];
        }

        return $tree;
    }

    /**
     * With no item active by route, marks the item whose own URL is the longest one the
     * current path equals or sits under, and opens its parent. Other hosts never match.
     *
     * @param  list<array{key: string, label: string, icon: string, single: bool, open: bool, items: list<array{label: string, icon: string, href: string, target: ?string, active: bool}>}>  $tree
     * @return list<array{key: string, label: string, icon: string, single: bool, open: bool, items: list<array{label: string, icon: string, href: string, target: ?string, active: bool}>}>
     */
    private function markUrlMatch(array $tree, string $currentPath): array
    {
        foreach ($tree as $group) {
            foreach ($group['items'] as $item) {
                if ($item['active']) {
                    return $tree;
                }
            }
        }

        $current = trim($currentPath, '/');
        $host = parse_url(url('/'), PHP_URL_HOST);
        $best = null;
        $bestLength = -1;

        foreach ($tree as $g => $group) {
            foreach ($group['items'] as $i => $item) {
                $parts = parse_url($item['href']);
                if (($parts['host'] ?? $host) !== $host) {
                    continue;
                }

                $path = trim($parts['path'] ?? '', '/');
                $matches = $path === $current || ($path !== '' && str_starts_with($current, $path.'/'));

                if ($matches && strlen($path) > $bestLength) {
                    $best = [$g, $i];
                    $bestLength = strlen($path);
                }
            }
        }

        if ($best !== null) {
            [$g, $i] = $best;
            $tree[$g]['items'][$i]['active'] = true;
            $tree[$g]['open'] = true;
        }

        return $tree;
    }

    /**
     * Every page every enabled module declares.
     *
     * @return list<Item>
     */
    private function declaredItems(): array
    {
        $items = [];

        foreach (Module::allEnabled() as $module) {
            // A central module's pages never show inside a tenant, nor a tenant module's centrally.
            if (! Tenancy::moduleBelongsHere($module)) {
                continue;
            }

            foreach ((array) config(strtolower($module->getName()).'.menu', []) as $item) {
                if (is_array($item) && isset($item['group'], $item['label'])) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * @param  Item  $item
     */
    private function visible(array $item, User $user): bool
    {
        if (isset($item['route']) && ! Route::has($item['route'])) {
            return false;
        }

        if (! isset($item['route']) && empty($item['url'])) {
            return false;
        }

        $decision = Foundation::resolveSidebarVisibility($item, $user);

        if ($decision !== null) {
            return $decision;
        }

        $permissions = $item['permissions'] ?? [];

        return $permissions === [] || $user->canAny($permissions);
    }

    /**
     * @param  Item  $item
     * @return list<string>
     */
    private function activeRoutes(array $item): array
    {
        $routes = $item['routes'] ?? [];

        if (isset($item['route'])) {
            $routes[] = $item['route'];
        }

        return array_values(array_unique($routes));
    }
}
