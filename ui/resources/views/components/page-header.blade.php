@props([
    'title'    => '',
    'subtitle' => null,
    'icon'     => null,
    'backUrl'  => null,
    'backLabel'=> null,
])

<div class="fd-page-head">
    <div class="fd-page-head-main">
        @if($icon)
            <span class="fd-icon-tile fd-icon-tile-lg"><i class="{{ $icon }}"></i></span>
        @endif
        <div class="min-width-0">
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
                <i class="ph-arrow-left"></i>{{ $backLabel ?? __('foundation::foundation.components.back') }}
            </a>
        @endif
    </div>
</div>
