<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Mrj\Foundation\Contracts\ImpersonationContext;
use OwenIt\Auditing\Contracts\UserResolver;
use OwenIt\Auditing\Resolvers\UserResolver as DefaultUserResolver;

/**
 * Attributes audits to the real actor: while a Super Admin is impersonating,
 * changes are recorded against the Super Admin, and the impersonated account
 * is kept in the audit's `impersonating:{id}` tag (see Mrj\Foundation\Models\Audit).
 *
 * @internal
 */
final class ImpersonationAwareAuditUserResolver implements UserResolver
{
    /**
     * @return Authenticatable|null
     */
    public static function resolve()
    {
        return app(ImpersonationContext::class)->impersonator() ?? DefaultUserResolver::resolve();
    }
}
