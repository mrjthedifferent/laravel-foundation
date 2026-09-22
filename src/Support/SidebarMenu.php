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
 * one of its permissions. A project can add its own rule for items carrying custom
 * flags through Foundation::sidebarVisibility().
 *
 * @phpstan-type Item array{group: string, label: string, icon: string, route?: string, url?: string, target?: string, routes?: list<string>, permissions?: list<string>, order?: int}
 *
 * @internal
 */
final readonly class SidebarMenu
{
    /**
     * @return list<array{key: string, label: string, icon: string, single: bool, open: bool, items: list<array{label: string, icon: string, href: string, target: ?string, active: bool}>}>
     */
    public function forUser(?User $user, ?string $currentRoute): array
    {
        if ($user === null) {
            return [];
        }

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
     * Every page every enabled module declares.
     *
     * @return list<Item>
     */
    private function declaredItems(): array
    {
        $items = [];

        foreach (Module::allEnabled() as $module) {
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
