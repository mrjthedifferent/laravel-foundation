<?php

namespace Mrj\Foundation\Services\Dashboard;

use Mrj\Foundation\Enums\ModuleContext;
use Mrj\Foundation\Support\ChartComposer;
use Mrj\Foundation\Support\Tenancy;

/**
 * Holds the chart composers the enabled modules offer. The dashboard draws the
 * first one the viewer may see, ordered by priority, so the User module's
 * sign-ins chart wins where that module is enabled and the package's new-users
 * fallback covers the case where it is not. With foundation.tenancy enabled,
 * only composers of modules that belong where the app is now take part.
 *
 * @internal
 */
final class ChartRegistry
{
    /** @var array<class-string<ChartComposer>, ModuleContext> */
    private array $composers = [];

    /**
     * @param  class-string<ChartComposer>  $composer
     */
    public function register(string $composer, ModuleContext $context = ModuleContext::Universal): void
    {
        $this->composers[$composer] ??= $context;
    }

    /**
     * @return array{label: string, series: array<string, int>}|null
     */
    public function first(int $days): ?array
    {
        $candidates = [];

        foreach ($this->composers as $class => $context) {
            if (! Tenancy::enabled() || $context->belongsTo(Tenancy::current())) {
                $candidates[] = app($class);
            }
        }

        usort($candidates, fn (ChartComposer $a, ChartComposer $b): int => $a->priority() <=> $b->priority());

        foreach ($candidates as $composer) {
            $series = $composer->series($days);

            if ($series !== null) {
                return ['label' => $composer->label($days), 'series' => $series];
            }
        }

        return null;
    }
}
