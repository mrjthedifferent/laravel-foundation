<?php

namespace Modules\RolePermission\View\Composers;

use Illuminate\View\View;
use Mrj\Foundation\Services\Dashboard\DashboardCache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Supplies the Roles & Permissions dashboard widget. The queries live here rather than in the
 * Blade partial so they can be cached and so the view stays free of data access.
 *
 * These are unscoped, application-wide totals, so a single shared cache key is safe.
 * A widget that ever becomes user-scoped must gain a scope segment in its key.
 */
final readonly class RolePermissionWidgetComposer
{
    public function __construct(private DashboardCache $cache) {}

    public function compose(View $view): void
    {
        $view->with('widget', $this->data());
    }

    /**
     * @return array<string, mixed>|null null when the viewer may not see the widget
     */
    private function data(): ?array
    {
        if (! auth()->user()?->hasAnyPermission(['View Role', 'Assign Permission'])) {
            return null;
        }

        return $this->cache->remember('widget:rolepermission', fn (): array => [
            'total_roles' => Role::query()->count(),
            'total_permissions' => Permission::query()->count(),
        ]);
    }
}
