@if ($hasData)
    <div class="fd-donut">
        <div class="fd-donut-plot">
            <svg viewBox="0 0 36 36" role="img" aria-label="{{ $summary }}">
                <circle class="fd-donut-track" cx="18" cy="18" r="15.9155" fill="none" stroke-width="4"></circle>
                @foreach ($segments as $segment)
                    @if ($segment['percent'] > 0)
                        {{-- A hairline gap between neighbours keeps small slices readable. --}}
                        <circle class="fd-donut-segment is-{{ $segment['tone'] }}" cx="18" cy="18" r="15.9155" fill="none"
                            stroke-width="4" stroke-dasharray="{{ max($segment['percent'] - 0.6, 0.1) }} {{ 100 - max($segment['percent'] - 0.6, 0.1) }}"
                            stroke-dashoffset="{{ $segment['offset'] }}" transform="rotate(-90 18 18)"></circle>
                    @endif
                @endforeach
            </svg>
            <div class="fd-donut-center">
                <span class="fd-donut-total">{{ number_format($total) }}</span>
                <span class="fd-donut-caption">{{ $label }}</span>
            </div>
        </div>
        <ul class="fd-donut-legend">
            @foreach ($segments as $segment)
                <li>
                    <span class="fd-donut-dot is-{{ $segment['tone'] }}"></span>
                    <span class="fd-donut-name">{{ $segment['label'] }}</span>
                    <span class="fd-donut-value">{{ number_format($segment['value']) }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@else
    <div class="fd-empty">
        <span class="fd-empty-icon"><i class="ph ph-chart-pie-slice"></i></span>
        <p class="fd-empty-title">{{ __('foundation::foundation.dashboard.chart_empty') }}</p>
    </div>
@endif
