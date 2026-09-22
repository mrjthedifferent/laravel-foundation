<?php

namespace Mrj\Foundation\Services\Dashboard;

use Mrj\Foundation\Support\StatComposer;

/**
 * Collects the headline stats the enabled modules contribute to the dashboard.
 * A module registers its composer through $dashboardStats on its service
 * provider, so a disabled module takes its stats with it and the core never
 * has to know which modules exist.
 *
 * @internal
 */
final class StatRegistry
{
    /** @var list<class-string<StatComposer>> */
    private array $composers = [];

    /**
     * @param  class-string<StatComposer>  $composer
     */
    public function register(string $composer): void
    {
        if (! in_array($composer, $this->composers, true)) {
            $this->composers[] = $composer;
        }
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

        foreach ($this->composers as $index => $class) {
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
