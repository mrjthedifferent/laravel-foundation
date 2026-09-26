<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Tenancy;

use Mrj\Foundation\Contracts\TenancyContext;
use Mrj\Foundation\Enums\ModuleContext;

/**
 * Stands in for a tenancy library: a test sets $tenant to "initialise" one.
 */
final class FakeTenancyContext implements TenancyContext
{
    public ?string $tenant = null;

    public function inTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function tenantKey(): string|int|null
    {
        return $this->tenant;
    }

    public function current(): ModuleContext
    {
        return $this->inTenant() ? ModuleContext::Tenant : ModuleContext::Central;
    }
}
