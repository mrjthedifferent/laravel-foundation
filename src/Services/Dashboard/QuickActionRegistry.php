<?php

namespace Mrj\Foundation\Services\Dashboard;

use Mrj\Foundation\Support\QuickActionComposer;

/**
 * The shortcuts the enabled modules offer, filtered to what the viewer may do.
 *
 * @internal
 */
final class QuickActionRegistry
{
    /** @use RegistersComposers<QuickActionComposer> */
    use RegistersComposers;

    /**
     * @return list<array{label: string, icon: string, href: string}>
     */
    public function all(): array
    {
        $composers = $this->instances();
        usort($composers, fn (QuickActionComposer $a, QuickActionComposer $b): int => $a->priority() <=> $b->priority());

        $user = auth()->user();
        $actions = [];

        foreach ($composers as $composer) {
            foreach ($composer->actions() as $action) {
                if (isset($action['permission']) && ! $user?->can($action['permission'])) {
                    continue;
                }

                $actions[] = ['label' => $action['label'], 'icon' => $action['icon'], 'href' => $action['href']];
            }
        }

        return $actions;
    }
}
