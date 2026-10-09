@if ($hasData)
    <div class="fd-heat" role="img" aria-label="{{ $summary }}">
        <div class="fd-heat-hours" aria-hidden="true">
            @foreach ($hours as $hour)
                <span>{{ $hour }}</span>
            @endforeach
        </div>
        @foreach ($rows as $row)
            <div class="fd-heat-row">
                <span class="fd-heat-day">{{ $row['label'] }}</span>
                <div class="fd-heat-cells">
                    @foreach ($row['cells'] as $cell)
                        <span class="fd-heat-cell is-l{{ $cell['level'] }}" title="{{ $cell['title'] }}"></span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="fd-empty">
        <span class="fd-empty-icon"><i class="ph ph-grid-four"></i></span>
        <p class="fd-empty-title">{{ __('foundation::foundation.dashboard.chart_empty') }}</p>
        <p class="fd-empty-text">{{ __('foundation::foundation.dashboard.chart_empty_text') }}</p>
    </div>
@endif
