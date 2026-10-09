@if ($widget)
    <div class="col-span-12 xl:col-span-4 lg:col-span-6">
        <div class="card h-full">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-neutral"><i class="ph-download-simple"></i></span>
                <h2 class="card-title">{{ __('importdownloadmanager::importdownloadmanager.widget.title') }}</h2>
                <a href="{{ route('admin.download.import.manager.index') }}" class="ms-auto text-sm">
                    {{ __('importdownloadmanager::importdownloadmanager.widget.view_all') }}
                </a>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-4">
                        <div class="fd-stat-value">{{ number_format($widget['total_jobs']) }}</div>
                        <div class="fd-stat-label">{{ __('importdownloadmanager::importdownloadmanager.widget.total_jobs') }}</div>
                    </div>
                    <div class="col-span-4">
                        <div class="fd-stat-value">{{ number_format($widget['pending_jobs']) }}</div>
                        <div class="fd-stat-label">{{ __('importdownloadmanager::importdownloadmanager.enums.status.pending') }}</div>
                    </div>
                    <div class="col-span-4">
                        <div class="fd-stat-value">{{ number_format($widget['completed_jobs']) }}</div>
                        <div class="fd-stat-label">{{ __('importdownloadmanager::importdownloadmanager.enums.status.completed') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
