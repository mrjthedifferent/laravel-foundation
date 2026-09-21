<?php

namespace Mrj\Foundation\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public function __construct(
        public bool $active,
        public string $activeLabel = 'Active',
        public string $inactiveLabel = 'Inactive',
    ) {}

    public function render(): View
    {
        return view('components.status-badge');
    }
}
