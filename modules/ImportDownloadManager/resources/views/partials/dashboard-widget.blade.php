@if ($widget)
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-neutral"><i class="ph-download-simple"></i></span>
                <h6 class="card-title">{{ __('importdownloadmanager::importdownloadmanager.widget.title') }}</h6>
                <a href="{{ route('admin.download.import.manager.index') }}" class="ms-auto fs-sm">
                    {{ __('importdownloadmanager::importdownloadmanager.widget.view_all') }}
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="fd-stat-value">{{ number_format($widget['total_jobs']) }}</div>
                        <div class="fd-stat-label">{{ __('importdownloadmanager::importdownloadmanager.widget.total_jobs') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fd-stat-value">{{ number_format($widget['pending_jobs']) }}</div>
                        <div class="fd-stat-label">{{ __('importdownloadmanager::importdownloadmanager.enums.status.pending') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fd-stat-value">{{ number_format($widget['completed_jobs']) }}</div>
                        <div class="fd-stat-label">{{ __('importdownloadmanager::importdownloadmanager.enums.status.completed') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
