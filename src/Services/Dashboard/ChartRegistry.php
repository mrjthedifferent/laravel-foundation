<?php

namespace Mrj\Foundation\Services\Dashboard;

use Mrj\Foundation\Support\ChartComposer;

/**
 * Holds the chart composers the enabled modules offer. The dashboard draws the
 * first one the viewer may see, ordered by priority, so the User module's
 * sign-ins chart wins where that module is enabled and the package's new-users
 * fallback covers the case where it is not.
 *
 * @internal
 */
final class ChartRegistry
{
    /** @var list<class-string<ChartComposer>> */
    private array $composers = [];

    /**
     * @param  class-string<ChartComposer>  $composer
     */
    public function register(string $composer): void
    {
        if (! in_array($composer, $this->composers, true)) {
            $this->composers[] = $composer;
        }
    }

    /**
     * @return array{label: string, series: array<string, int>}|null
     */
    public function first(int $days): ?array
    {
        $candidates = array_map(fn (string $class): ChartComposer => app($class), $this->composers);

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
