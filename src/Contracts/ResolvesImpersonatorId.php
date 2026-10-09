<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

/**
 * An ImpersonationContext that can report the impersonator's id without loading
 * the user, for hot paths such as request logging. Kept apart from
 * ImpersonationContext so that interface stays source-compatible for projects
 * that implement it themselves.
 *
 * @api
 */
interface ResolvesImpersonatorId
{
    /**
     * Id of the real actor behind an impersonated session, or null when not impersonating.
     */
    public function impersonatorId(): ?int;
}
