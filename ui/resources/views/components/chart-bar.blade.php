@if ($hasData)
    <ul @class(['fd-bars', 'is-labelled' => $labels]) role="img" aria-label="{{ $summary }}">
        @foreach ($bars as $bar)
            <li>
                <span class="fd-bars-label">{{ $bar['label'] }}</span>
                @if ($labels)
                    {{-- Direct label: the value sits at the end of its bar --}}
                    <span class="fd-bars-track" style="--fd-bar: {{ $bar['percent'] / 100 }}">
                        <span class="fd-bars-fill"></span><span class="fd-bars-value">{{ number_format($bar['value']) }}</span>
                    </span>
                @else
                    <span class="fd-bars-track"><span class="fd-bars-fill" style="width: {{ $bar['percent'] }}%"></span></span>
                    <span class="fd-bars-value">{{ number_format($bar['value']) }}</span>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <div class="fd-empty">
        <span class="fd-empty-icon"><i class="ph ph-chart-bar"></i></span>
        <p class="fd-empty-title">{{ __('foundation::foundation.dashboard.chart_empty') }}</p>
    </div>
@endif
