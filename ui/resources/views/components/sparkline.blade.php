@if ($hasData)
    <svg class="fd-spark" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="currentColor" stop-opacity=".2"></stop>
                <stop offset="1" stop-color="currentColor" stop-opacity="0"></stop>
            </linearGradient>
        </defs>
        <path d="{{ $areaPath }}" fill="url(#{{ $gradientId }})"></path>
        <path d="{{ $linePath }}" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"
            stroke-linecap="round" vector-effect="non-scaling-stroke"></path>
    </svg>
@endif
