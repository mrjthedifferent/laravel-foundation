@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <i class="ph-gear me-2 text-secondary"></i>
                <h6 class="card-title mb-0">{{ __('settings::settings.widget.title') }}</h6>
                @if (auth()->user()->hasAnyPermission(['Edit System Setting']))
                    <a href="{{ route('admin.settings.index') }}" class="ms-auto btn btn-sm btn-outline-secondary">
                        {{ __('settings::settings.widget.manage') }}
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-md-6">
                        <div class="fs-4 fw-bold">{{ number_format($widget['total_settings']) }}</div>
                        <div class="text-muted small">{{ __('settings::settings.widget.configured') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fs-4 fw-bold text-success">{{ number_format($widget['visible_settings']) }}</div>
                        <div class="text-muted small">{{ __('settings::settings.widget.visible') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
