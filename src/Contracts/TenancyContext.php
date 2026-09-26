<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

use Mrj\Foundation\Enums\ModuleContext;

/**
 * What the foundation needs to know about the current tenant, without depending
 * on a tenancy library. The default binding reports "no tenancy"; a project that
 * enables foundation.tenancy rebinds it to an adapter over its library.
 *
 * @api
 */
interface TenancyContext
{
    /**
     * A tenant is initialised for the current request, job or command.
     */
    public function inTenant(): bool;

    /**
     * The current tenant's key, or null in the central app.
     */
    public function tenantKey(): string|int|null;

    /**
     * Central or Tenant; never Universal.
     */
    public function current(): ModuleContext;
}
