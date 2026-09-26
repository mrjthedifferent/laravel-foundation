<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

use Mrj\Foundation\Contracts\TenancyContext;
use Mrj\Foundation\Enums\ModuleContext;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Nwidart\Modules\Module;

/**
 * The one place the package asks "is tenancy on, and where are we now?".
 * Every answer is the pre-tenancy behaviour while foundation.tenancy.enabled
 * is false, so call sites need no guard of their own.
 *
 * @api
 */
final class Tenancy
{
    public static function enabled(): bool
    {
        return config('foundation.tenancy.enabled') === true;
    }

    public static function context(): TenancyContext
    {
        return app(TenancyContext::class);
    }

    /**
     * Central or Tenant. Always Central while tenancy is disabled.
     */
    public static function current(): ModuleContext
    {
        return self::enabled() ? self::context()->current() : ModuleContext::Central;
    }

    public static function contextOf(Module|string $module): ModuleContext
    {
        if (is_string($module)) {
            if (! ModuleFacade::has($module)) {
                return ModuleContext::Universal;
            }

            $module = ModuleFacade::find($module);
        }

        return ModuleContext::of($module);
    }

    /**
     * Whether a module's pages, permissions and dashboard parts belong where the
     * app is now. Always true while tenancy is disabled.
     */
    public static function moduleBelongsHere(Module|string $module): bool
    {
        return ! self::enabled() || self::contextOf($module)->belongsTo(self::current());
    }

    /**
     * Whether an entry restricted to some contexts (a list of context values, or
     * null for "everywhere") belongs where the app is now.
     *
     * @param  list<string>|null  $contexts
     */
    public static function allows(?array $contexts): bool
    {
        return ! self::enabled() || $contexts === null || in_array(self::current()->value, $contexts, true);
    }

    /**
     * A cache key the package writes, prefixed per foundation.cache.prefix and,
     * inside a tenant, scoped to it. A cache store that already separates tenants
     * (stancl's tagged cache, for one) is unaffected by the extra segment.
     */
    public static function cacheKey(string $key): string
    {
        $prefix = (string) config('foundation.cache.prefix');

        if (! self::enabled() || ! self::context()->inTenant()) {
            return $prefix.$key;
        }

        return $prefix.'tenant.'.self::context()->tenantKey().'.'.$key;
    }
}
