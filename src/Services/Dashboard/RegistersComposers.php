<?php

namespace Mrj\Foundation\Services\Dashboard;

use Mrj\Foundation\Enums\ModuleContext;
use Mrj\Foundation\Support\Tenancy;

/**
 * What the dashboard registries share: a class is registered once with the context its
 * module belongs to, and with foundation.tenancy enabled only the ones that belong where
 * the app is now take part. A disabled module registers nothing, so it takes its widgets
 * with it and the core never has to know which modules exist.
 *
 * @template TItem of object
 *
 * @internal
 */
trait RegistersComposers
{
    /** @var array<class-string<TItem>, ModuleContext> */
    private array $composers = [];

    /**
     * @param  class-string<TItem>  $composer
     */
    public function register(string $composer, ModuleContext $context = ModuleContext::Universal): void
    {
        $this->composers[$composer] ??= $context;
    }

    /**
     * @return list<TItem>
     */
    private function instances(): array
    {
        $instances = [];

        foreach ($this->composers as $class => $context) {
            if (Tenancy::enabled() && ! $context->belongsTo(Tenancy::current())) {
                continue;
            }

            $instances[] = app($class);
        }

        return $instances;
    }
}
