@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <i class="ph-lock-key me-2 text-primary"></i>
                <h6 class="card-title mb-0">{{ __('otp::otp.widget.title') }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold text-primary">{{ number_format($widget['verified_today']) }}</div>
                        <div class="text-muted small">{{ __('otp::otp.widget.verified_today') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold">{{ number_format($widget['whitelist_count']) }}</div>
                        <div class="text-muted small">{{ __('otp::otp.widget.whitelist_label') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold text-success">{{ number_format($widget['active_whitelist']) }}</div>
                        <div class="text-muted small">{{ __('foundation::foundation.common.active') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
