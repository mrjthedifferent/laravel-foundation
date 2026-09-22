@if ($widget)
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-neutral"><i class="ph-gear"></i></span>
                <h6 class="card-title">{{ __('settings::settings.widget.title') }}</h6>
                @if (auth()->user()->can('Edit System Setting'))
                    <a href="{{ route('admin.settings.index') }}" class="ms-auto fs-sm">
                        {{ __('settings::settings.widget.manage') }}
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="fd-stat-value">{{ number_format($widget['total_settings']) }}</div>
                        <div class="fd-stat-label">{{ __('settings::settings.widget.configured') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="fd-stat-value">{{ number_format($widget['visible_settings']) }}</div>
                        <div class="fd-stat-label">{{ __('settings::settings.widget.visible') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
