@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <i class="ph-download me-2 text-teal"></i>
                <h6 class="card-title mb-0">Import / Download</h6>
                <a href="{{ route('admin.download.import.manager.index') }}"
                    class="ms-auto btn btn-sm btn-outline-secondary">
                    View all
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold">{{ number_format($widget['total_jobs']) }}</div>
                        <div class="text-muted small">Total Jobs</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold text-warning">{{ number_format($widget['pending_jobs']) }}</div>
                        <div class="text-muted small">Pending</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold text-success">{{ number_format($widget['completed_jobs']) }}</div>
                        <div class="text-muted small">Completed</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
