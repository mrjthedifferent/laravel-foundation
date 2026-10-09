<div class="card h-full">
    <div class="card-header">
        <span class="fd-icon-tile fd-icon-tile-sm is-info"><i class="ph-grid-four"></i></span>
        <h2 class="card-title">{{ __('user::user.widget.heatmap_title') }}</h2>
        <span class="text-muted text-sm ms-auto">{{ __('user::user.widget.heatmap_range', ['days' => $days]) }}</span>
    </div>
    <div class="card-body">
        <x-chart-heatmap :matrix="$matrix" :label="__('user::user.widget.heatmap_title')" />
    </div>
</div>
