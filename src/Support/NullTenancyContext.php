<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

use Mrj\Foundation\Contracts\TenancyContext;
use Mrj\Foundation\Enums\ModuleContext;

/** @internal */
final class NullTenancyContext implements TenancyContext
{
    public function inTenant(): bool
    {
        return false;
    }

    public function tenantKey(): string|int|null
    {
        return null;
    }

    public function current(): ModuleContext
    {
        return ModuleContext::Central;
    }
}
