@if ($hasData)
    <ul class="fd-bars" role="img" aria-label="{{ $summary }}">
        @foreach ($bars as $bar)
            <li>
                <span class="fd-bars-label">{{ $bar['label'] }}</span>
                <span class="fd-bars-track"><span class="fd-bars-fill" style="width: {{ $bar['percent'] }}%"></span></span>
                <span class="fd-bars-value">{{ number_format($bar['value']) }}</span>
            </li>
        @endforeach
    </ul>
@else
    <div class="fd-empty">
        <span class="fd-empty-icon"><i class="ph-chart-bar"></i></span>
        <p class="fd-empty-title">{{ __('foundation::foundation.dashboard.chart_empty') }}</p>
    </div>
@endif
