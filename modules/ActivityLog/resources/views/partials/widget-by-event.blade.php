<div class="card h-full">
    <div class="card-header">
        <span class="fd-icon-tile fd-icon-tile-sm is-success"><i class="ph ph-chart-bar-horizontal"></i></span>
        <h2 class="card-title">{{ __('activitylog::activitylog.widget.by_event_title') }}</h2>
        <span class="text-muted text-sm ms-auto">{{ __('activitylog::activitylog.widget.last_days', ['days' => $days]) }}</span>
    </div>
    <div class="card-body">
        <x-chart-bar :series="$series" :label="__('activitylog::activitylog.widget.by_event_title')" />
    </div>
</div>
