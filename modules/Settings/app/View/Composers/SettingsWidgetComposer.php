<?php

namespace Modules\Settings\View\Composers;

use Modules\Settings\Models\Setting;
use Mrj\Foundation\Support\WidgetComposer;
use Override;

/**
 * Supplies the Settings dashboard widget.
 *
 * This is an unscoped, application-wide total, so a single shared cache key
 * is safe. A widget that ever becomes user-scoped must gain a scope segment
 * in its key.
 */
final class SettingsWidgetComposer extends WidgetComposer
{
    #[Override]
    protected function permissions(): array
    {
        return ['Edit System Setting', 'Edit Special Setting', 'Developer Setting'];
    }

    #[Override]
    protected function key(): string
    {
        return 'settings';
    }

    #[Override]
    protected function build(): array
    {
        return [
            'total_settings' => Setting::query()->count(),
            'visible_settings' => Setting::query()->where('is_visible', true)->count(),
        ];
    }
}
