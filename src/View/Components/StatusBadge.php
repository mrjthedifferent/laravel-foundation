<?php

namespace Mrj\Foundation\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public string $activeLabel;

    public string $inactiveLabel;

    /**
     * The labels default to the translated "Active"/"Inactive"; a default parameter value
     * must be a constant expression, so the translation is resolved here instead.
     */
    public function __construct(
        public bool $active,
        ?string $activeLabel = null,
        ?string $inactiveLabel = null,
    ) {
        $this->activeLabel = $activeLabel ?? __('foundation::foundation.common.active');
        $this->inactiveLabel = $inactiveLabel ?? __('foundation::foundation.common.inactive');
    }

    public function render(): View
    {
        return view('components.status-badge');
    }
}
