<?php

declare(strict_types=1);

namespace Mrj\Foundation\Enums;

use Nwidart\Modules\Module;

/**
 * Where a module runs when foundation.tenancy is enabled. A module declares it
 * in module.json as "context"; a module that declares nothing is universal.
 *
 * - Universal: routes and tables exist in the central app and in every tenant.
 * - Central: only the central app (its domains, its database).
 * - Tenant: only inside an initialised tenant.
 *
 * @api
 */
enum ModuleContext: string
{
    case Universal = 'universal';
    case Central = 'central';
    case Tenant = 'tenant';

    public function label(): string
    {
        return match ($this) {
            self::Universal => __('Universal'),
            self::Central => __('Central'),
            self::Tenant => __('Tenant'),
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }

    public static function of(Module $module): self
    {
        return self::tryFrom((string) $module->get('context', self::Universal->value)) ?? self::Universal;
    }

    /**
     * Whether something declared for this context belongs where the app is now
     * (always Central or Tenant). Universal belongs everywhere.
     */
    public function belongsTo(self $current): bool
    {
        return $this === self::Universal || $this === $current;
    }
}
