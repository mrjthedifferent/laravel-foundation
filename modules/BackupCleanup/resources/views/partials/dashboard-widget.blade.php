@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <i class="ph-database me-2 text-danger"></i>
                <h6 class="card-title mb-0">Backup</h6>
                @can('View Backup')
                    <a href="{{ route('admin.backups.index') }}" class="ms-auto btn btn-sm btn-outline-danger">
                        View all
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="row g-3 text-center mb-3">
                    <div class="col-md-12">
                        <div class="fs-4 fw-bold text-danger">{{ number_format($widget['count']) }}</div>
                        <div class="text-muted small">Backup Files</div>
                    </div>
                </div>
                @if ($widget['latest'])
                    <div class="border-top pt-2">
                        <div class="text-muted small fw-semibold mb-1 text-uppercase">Last Backup</div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="ph-file-zip text-muted"></i>
                            <div class="flex-grow-1 min-width-0">
                                <div class="small text-truncate">{{ $widget['latest']['filename'] }}</div>
                                <div class="text-muted small">
                                    {{ \Carbon\Carbon::createFromTimestamp($widget['latest']['date'])->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center text-muted small border-top pt-2">No backups found</div>
                @endif
            </div>
        </div>
    </div>
@endif
