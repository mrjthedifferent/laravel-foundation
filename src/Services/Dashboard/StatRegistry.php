<?php

namespace Mrj\Foundation\Services\Dashboard;

use Mrj\Foundation\Enums\ModuleContext;
use Mrj\Foundation\Support\StatComposer;
use Mrj\Foundation\Support\Tenancy;

/**
 * Collects the headline stats the enabled modules contribute to the dashboard.
 * A module registers its composer through $dashboardStats on its service
 * provider, so a disabled module takes its stats with it and the core never
 * has to know which modules exist. With foundation.tenancy enabled, only the
 * stats of modules that belong where the app is now are shown.
 *
 * @internal
 */
final class StatRegistry
{
    /** @var array<class-string<StatComposer>, ModuleContext> */
    private array $composers = [];

    /**
     * @param  class-string<StatComposer>  $composer
     */
    public function register(string $composer, ModuleContext $context = ModuleContext::Universal): void
    {
        $this->composers[$composer] ??= $context;
    }

    /**
     * Every stat the viewer may see, ordered by the contributing module's
     * priority and then by registration order.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $collected = [];
        $index = 0;

        foreach ($this->composers as $class => $context) {
            $index++;

            if (Tenancy::enabled() && ! $context->belongsTo(Tenancy::current())) {
                continue;
            }

            $composer = app($class);

            foreach ($composer->stats() as $position => $stat) {
                $collected[] = [
                    'sort' => [$composer->priority(), $index, $position],
                    'stat' => $stat,
                ];
            }
        }

        usort($collected, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return array_map(fn (array $entry): array => $entry['stat'], $collected);
    }
}
