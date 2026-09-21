@props([
    'title'    => '',
    'subtitle' => null,
    'icon'     => null,
    'backUrl'  => null,
    'backLabel'=> 'Back',
])

<div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
    <div class="d-flex align-items-center gap-3">
        @if($icon)
            <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:38px;height:38px;font-size:1.2rem;">
                <i class="{{ $icon }}"></i>
            </div>
        @endif
        <div>
            <h5 class="mb-0 fw-bold lh-1">{{ $title }}</h5>
            @if($subtitle)
                <div class="text-muted fs-xs mt-1">{{ $subtitle }}</div>
            @endif
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        @isset($actions)
            {{ $actions }}
        @endisset
        @if($backUrl)
            <a href="{{ $backUrl }}" class="btn btn-sm btn-outline-secondary">
                <i class="ph-arrow-left me-1"></i>{{ $backLabel }}
            </a>
        @endif
    </div>
</div>
