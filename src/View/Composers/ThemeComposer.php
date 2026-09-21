<?php

namespace Mrj\Foundation\View\Composers;

use Illuminate\View\View;
use Mrj\Foundation\Services\ThemeResolver;

class ThemeComposer
{
    public function __construct(
        private ThemeResolver $themeResolver
    ) {}

    public function compose(View $view): void
    {
        $viewName = $view->getName();
        $theme = in_array($viewName, ['layouts.guest', 'errors.layout', 'errors.minimal'], true)
            ? $this->themeResolver->resolveForGuest()
            : $this->themeResolver->resolve();

        $view->with('theme', $theme);
    }
}
