<?php

declare(strict_types=1);

namespace Modules\Settings\View\Composers;

use Mrj\Foundation\Support\QuickActionComposer;
use Override;

/**
 * A shortcut to the general settings.
 */
final class SettingsQuickActions extends QuickActionComposer
{
    #[Override]
    public function priority(): int
    {
        return 60;
    }

    #[Override]
    public function actions(): array
    {
        return [[
            'label' => __('settings::settings.quick.general'),
            'icon' => 'ph-gear',
            'href' => route('admin.settings.index'),
            'permission' => 'Edit System Setting',
        ]];
    }
}
