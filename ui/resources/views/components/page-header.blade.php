@props([
    'title'    => '',
    'subtitle' => null,
    'icon'     => null,
    'backUrl'  => null,
    'backLabel'=> null,
])

{{-- The one page header: an icon tile, the title and a line of context on the left, the page's
     primary actions on the right, and optional tabs underneath. List pages keep their title in the
     table card's toolbar instead. --}}
<header class="fd-page-head">
    <div class="fd-page-head-main">
        @if($icon)
            <span class="fd-icon-tile fd-icon-tile-lg"><i class="{{ $icon }}"></i></span>
        @endif
        <div class="min-w-0">
            <h1 class="fd-page-title">{{ $title }}</h1>
            @if($subtitle)
                <p class="fd-page-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="fd-page-actions">
        @isset($actions)
            {{ $actions }}
        @endisset
        @if($backUrl)
            <a href="{{ $backUrl }}" class="btn btn-light">
                <i class="ph ph-arrow-left"></i>{{ $backLabel ?? __('foundation::foundation.components.back') }}
            </a>
        @endif
    </div>

    @isset($tabs)
        <div class="fd-page-tabs">{{ $tabs }}</div>
    @endisset
</header>
