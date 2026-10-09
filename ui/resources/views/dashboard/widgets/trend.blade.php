<div class="card h-full">
    <div class="card-header">
        <h2 class="card-title">{{ $chart['label'] }}</h2>
        <div class="fd-chart-total ms-auto">
            <span class="fd-chart-total-value">{{ number_format($total) }}</span>
            @if ($delta !== null && $delta != 0)
                <span class="fd-delta {{ $delta > 0 ? 'is-up' : 'is-down' }}">
                    <i class="{{ $delta > 0 ? 'ph-arrow-up-right' : 'ph-arrow-down-right' }}"></i>{{ number_format(abs($delta), abs($delta) < 10 ? 1 : 0) }}%
                </span>
                <span class="fd-chart-total-note">{{ __('foundation::foundation.dashboard.vs_previous') }}</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        <x-chart-area :series="$chart['series']" :previous="$chart['previous'] ?? []" :label="$chart['label']" />
        @if (isset($chart['previous']))
            <p class="fd-chart-legend"><span class="is-current"></span>{{ __('foundation::foundation.dashboard.this_period') }}<span class="is-previous"></span>{{ __('foundation::foundation.dashboard.previous_period') }}</p>
        @endif
    </div>
</div>
