<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

/**
 * Base for one line of the dashboard's "System health" card: a thing that is fine,
 * needs a look, or is broken. A module lists its checks in $dashboardHealth on its
 * service provider.
 *
 * The card lists the checks that are not fine first, so an administrator sees what
 * needs attention without reading the rest.
 *
 * @api
 */
abstract class HealthCheck
{
    public const string OK = 'ok';

    public const string WARN = 'warn';

    public const string FAIL = 'fail';

    /**
     * Permissions that allow seeing this check, any one of them is enough.
     *
     * @return list<string>
     */
    abstract public function permissions(): array;

    /**
     * status is one of OK, WARN and FAIL.
     *
     * @return array{status: string, label: string, detail?: string, href?: string}
     */
    abstract public function check(): array;

    /**
     * Where this check sits among the others of the same status: lower comes first.
     */
    public function priority(): int
    {
        return 50;
    }
}
