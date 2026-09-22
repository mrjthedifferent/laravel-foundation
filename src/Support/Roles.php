<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

/**
 * The three role names the package ships and seeds
 * (RolePermissionDatabaseSeeder). A project that renames them sets
 * config/foundation.php's 'roles' key rather than editing role checks
 * scattered through the codebase.
 *
 * @api
 */
final class Roles
{
    public static function superAdmin(): string
    {
        return (string) config('foundation.roles.super_admin', 'Super Admin');
    }

    public static function admin(): string
    {
        return (string) config('foundation.roles.admin', 'Admin');
    }

    public static function user(): string
    {
        return (string) config('foundation.roles.user', 'User');
    }
}
