@props([
    'label'  => '',
    'value'  => '',
    'icon'   => 'ph-chart-bar',
    'color'  => 'primary',  # {{-- primary | success | warning | danger | info --}}
    'href'   => null,
    'change' => null,       # {{-- e.g. '+12%' --}}
    'changeUp' => true,
    'caption' => null,      # {{-- what the change is measured against --}}
])

@php
    $tone = match ($color) {
        'success' => 'is-success',
        'warning' => 'is-warning',
        'danger'  => 'is-danger',
        'info'    => 'is-info',
        'secondary' => 'is-neutral',
        default   => '',
    };
@endphp

<div class="card h-100">
    <div class="fd-stat">
        <div class="fd-stat-head">
            <span class="fd-stat-label">{{ $label }}</span>
            <span class="fd-icon-tile fd-icon-tile-sm {{ $tone }}"><i class="{{ $icon }}"></i></span>
        </div>

        <div class="fd-stat-value">
            @if($href)
                <a href="{{ $href }}" class="text-reset text-decoration-none stretched-link">{{ $value }}</a>
            @else
                {{ $value }}
            @endif
        </div>

        @if($change !== null || $caption)
            <div class="fd-stat-foot">
                @if($change !== null)
                    <span class="fd-delta {{ $changeUp ? 'is-up' : 'is-down' }}">
                        <i class="{{ $changeUp ? 'ph-arrow-up-right' : 'ph-arrow-down-right' }}"></i>{{ $change }}
                    </span>
                @endif
                @if($caption)
                    {{ $caption }}
                @endif
            </div>
        @endif
    </div>
</div>
