@if ($widget)
    <div class="col-span-12 xl:col-span-4 lg:col-span-6">
        <div class="card h-full">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-neutral"><i class="ph-gear"></i></span>
                <h2 class="card-title">{{ __('settings::settings.widget.title') }}</h2>
                @if (auth()->user()->can('Edit System Setting'))
                    <a href="{{ route('admin.settings.index') }}" class="ms-auto text-sm">
                        {{ __('settings::settings.widget.manage') }}
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-6">
                        <div class="fd-stat-value">{{ number_format($widget['total_settings']) }}</div>
                        <div class="fd-stat-label">{{ __('settings::settings.widget.configured') }}</div>
                    </div>
                    <div class="col-span-6">
                        <div class="fd-stat-value">{{ number_format($widget['visible_settings']) }}</div>
                        <div class="fd-stat-label">{{ __('settings::settings.widget.visible') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
