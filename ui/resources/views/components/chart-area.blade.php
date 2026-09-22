@if ($hasData)
    {{-- The plot keeps its own left-to-right coordinates, whatever the page direction. --}}
    <svg class="chart-area" viewBox="0 0 640 200" preserveAspectRatio="none" role="img"
        aria-label="{{ $summary }}">
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="var(--fd-accent)" stop-opacity=".22"></stop>
                <stop offset="1" stop-color="var(--fd-accent)" stop-opacity="0"></stop>
            </linearGradient>
        </defs>

        <g class="chart-grid">
            @foreach ($ticks as $tick)
                <line x1="36" y1="{{ $tick['y'] }}" x2="630" y2="{{ $tick['y'] }}"></line>
            @endforeach
        </g>

        <g class="chart-axis">
            @foreach ($ticks as $tick)
                <text x="0" y="{{ $tick['y'] + 4 }}">{{ number_format($tick['value']) }}</text>
            @endforeach
        </g>

        <path d="{{ $areaPath }}" fill="url(#{{ $gradientId }})"></path>
        <path d="{{ $linePath }}" fill="none" stroke="var(--fd-accent)" stroke-width="2"
            stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"></path>
        <circle cx="{{ $lastX }}" cy="{{ $lastY }}" r="4" fill="var(--fd-surface)"
            stroke="var(--fd-accent)" stroke-width="2" vector-effect="non-scaling-stroke"></circle>
    </svg>
@else
    <div class="fd-empty">
        <span class="fd-empty-icon"><i class="ph-chart-line"></i></span>
        <p class="fd-empty-title">{{ __('foundation::foundation.dashboard.chart_empty') }}</p>
        <p class="fd-empty-text">{{ __('foundation::foundation.dashboard.chart_empty_text') }}</p>
    </div>
@endif
