<?php

namespace Mrj\Foundation\Models;

use Mrj\Foundation\Contracts\ImpersonationContext;
use OwenIt\Auditing\Models\Audit as BaseAudit;

/**
 * Audit record (config `audit.implementation`). Every audit written while a
 * Super Admin is impersonating is tagged `impersonating:{userId}`; the actor
 * itself is the Super Admin (Mrj\Foundation\Support\ImpersonationAwareAuditUserResolver).
 */
class Audit extends BaseAudit
{
    public const IMPERSONATION_TAG_PREFIX = 'impersonating:';

    protected static function booted(): void
    {
        static::creating(function (self $audit): void {
            $impersonatedUserId = app(ImpersonationContext::class)->impersonatedUserId();

            if ($impersonatedUserId === null) {
                return;
            }

            $audit->tags = implode(',', array_filter([
                $audit->tags,
                self::IMPERSONATION_TAG_PREFIX.$impersonatedUserId,
            ]));
        });
    }
}
