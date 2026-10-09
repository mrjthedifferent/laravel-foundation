<?php

declare(strict_types=1);

namespace Mrj\Foundation\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Mrj\Foundation\Contracts\ImpersonationContext;
use Mrj\Foundation\Contracts\ResolvesImpersonatorId;

/** @internal */
final class NullImpersonationContext implements ImpersonationContext, ResolvesImpersonatorId
{
    public function isImpersonating(): bool
    {
        return false;
    }

    public function impersonator(): ?Authenticatable
    {
        return null;
    }

    public function impersonatorId(): ?int
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
