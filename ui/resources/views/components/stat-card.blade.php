@props([
    'label'  => '',
    'value'  => '',
    'icon'   => 'ph-chart-bar',
    'color'  => 'primary',  # {{-- primary | success | warning | danger | info --}}
    'href'   => null,
    'change' => null,       # {{-- e.g. '+12%' --}}
    'changeUp' => true,
])

<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-{{ $color }} bg-opacity-10 text-{{ $color }} rounded d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:48px;height:48px;font-size:1.5rem;">
                <i class="{{ $icon }}"></i>
            </div>
            <div class="flex-fill min-width-0">
                <div class="text-muted fs-xs mb-1">{{ $label }}</div>
                <div class="fw-bold fs-5 lh-1">
                    @if($href)
                        <a href="{{ $href }}" class="text-body text-decoration-none stretched-link">{{ $value }}</a>
                    @else
                        {{ $value }}
                    @endif
                </div>
                @if($change !== null)
                    <div class="fs-xs mt-1 {{ $changeUp ? 'text-success' : 'text-danger' }}">
                        <i class="{{ $changeUp ? 'ph-trend-up' : 'ph-trend-down' }} me-1"></i>{{ $change }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

