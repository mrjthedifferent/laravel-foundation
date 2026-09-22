@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-info"><i class="ph-lock-key"></i></span>
                <h2 class="card-title">{{ __('otp::otp.widget.title') }}</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="fd-stat-value">{{ number_format($widget['verified_today']) }}</div>
                        <div class="fd-stat-label">{{ __('otp::otp.widget.verified_today') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fd-stat-value">{{ number_format($widget['whitelist_count']) }}</div>
                        <div class="fd-stat-label">{{ __('otp::otp.widget.whitelist_label') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fd-stat-value">{{ number_format($widget['active_whitelist']) }}</div>
                        <div class="fd-stat-label">{{ __('foundation::foundation.common.active') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
