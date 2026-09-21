<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * What the core needs to know about impersonation, without depending on the
 * module that implements it. The default binding reports "never impersonating";
 * the User module rebinds this to its real service.
 */
interface ImpersonationContext
{
    public function isImpersonating(): bool;

    /**
     * The real actor behind an impersonated session, or null when not impersonating.
     */
    public function impersonator(): ?Authenticatable;

    public function impersonatedUserId(): ?int;

    /**
     * End the impersonated session and return to the impersonator's own account.
     */
    public function stop(): void;
}
