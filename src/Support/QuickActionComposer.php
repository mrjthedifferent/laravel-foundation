<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

/**
 * Base for a module's shortcuts on the dashboard ("Add user", "Send notification").
 * A module lists its composers in $dashboardActions on its service provider.
 *
 * Each action carries the permission it needs, and the dashboard drops the ones the
 * viewer lacks, so a composer never has to check who is looking.
 *
 * @api
 */
abstract class QuickActionComposer
{
    /**
     * @return list<array{label: string, icon: string, href: string, permission?: string}>
     */
    abstract public function actions(): array;

    /**
     * Where this module's shortcuts sit among the others: lower comes first.
     */
    public function priority(): int
    {
        return 50;
    }
}
