@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <i class="ph-bell me-2 text-info"></i>
                <h6 class="card-title mb-0">Notifications</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold">{{ number_format($widget['total']) }}</div>
                        <div class="text-muted small">Total</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold text-warning">{{ number_format($widget['unread']) }}</div>
                        <div class="text-muted small">Unread</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold text-info">{{ number_format($widget['push_subscribers']) }}</div>
                        <div class="text-muted small">Push Subscribers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
