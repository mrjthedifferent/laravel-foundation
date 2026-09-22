<?php

namespace Mrj\Foundation\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The master layout every module's pages extend, replacing 9 near-identical
 * copies that differed only in the second breadcrumb's route and label:
 *
 *   <x-module-layout route="admin.users.index" label="User" />
 *
 * A page still extends the module's own layouts.master (which is now just
 * this one line) and fills in its breadcrumb and content sections — those
 * still resolve correctly from inside the component's own template, the
 * same as they already did through the app-layout component this one wraps.
 *
 * @internal
 */
final class ModuleLayout extends Component
{
    public function __construct(
        public string $route,
        public string $label,
    ) {}

    public function render(): View
    {
        return view('layouts.module');
    }
}
