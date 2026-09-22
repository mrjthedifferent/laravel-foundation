<?php

namespace Mrj\Foundation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mrj\Foundation\Contracts\ImpersonationContext;
use Mrj\Foundation\Database\Factories\AuditFactory;
use Override;
use OwenIt\Auditing\Models\Audit as BaseAudit;

/**
 * Audit record (config `audit.implementation`). Every audit written while a
 * Super Admin is impersonating is tagged `impersonating:{userId}`; the actor
 * itself is the Super Admin (Mrj\Foundation\Support\ImpersonationAwareAuditUserResolver).
 */
class Audit extends BaseAudit
{
    use HasFactory;

    public const IMPERSONATION_TAG_PREFIX = 'impersonating:';

    /**
     * HasFactory's default guess is Database\Factories\{model}Factory in the
     * app namespace; the package's factories live in its own namespace.
     */
    protected static function newFactory(): AuditFactory
    {
        return AuditFactory::new();
    }

    #[Override]
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
