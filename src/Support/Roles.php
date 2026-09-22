<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

/**
 * The two role names the package seeds (RolePermissionDatabaseSeeder). A project
 * that renames them sets config/foundation.php's 'roles' key rather than editing role checks.
 *
 * Super Admin is not a role: see User::isSuperAdmin().
 *
 * @api
 */
final class Roles
{
    public static function admin(): string
    {
        return (string) config('foundation.roles.admin', 'Admin');
    }

    public static function user(): string
    {
        return (string) config('foundation.roles.user', 'User');
    }
}
