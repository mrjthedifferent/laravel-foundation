@if ($widget)
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-info"><i class="ph-bell"></i></span>
                <h2 class="card-title">{{ __('notification::notification.widget.title') }}</h2>
                <a href="{{ route('admin.notification.index') }}" class="ms-auto fs-sm">
                    {{ __('foundation::foundation.notification.view_all') }}
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="fd-stat-value">{{ number_format($widget['total']) }}</div>
                        <div class="fd-stat-label">{{ __('notification::notification.widget.total') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fd-stat-value">{{ number_format($widget['unread']) }}</div>
                        <div class="fd-stat-label">{{ __('notification::notification.widget.unread') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fd-stat-value">{{ number_format($widget['push_subscribers']) }}</div>
                        <div class="fd-stat-label">{{ __('notification::notification.widget.push_subscribers') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
