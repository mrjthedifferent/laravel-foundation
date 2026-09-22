<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Mrj\Foundation\Contracts\ImpersonationContext;

/** @internal */
final class NullImpersonationContext implements ImpersonationContext
{
    public function isImpersonating(): bool
    {
        return false;
    }

    public function impersonator(): ?Authenticatable
    {
        return null;
    }

    public function impersonatedUserId(): ?int
    {
        return null;
    }

    public function stop(): void
    {
        //
    }
}
