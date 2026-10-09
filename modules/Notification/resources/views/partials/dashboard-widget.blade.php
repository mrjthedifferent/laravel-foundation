@if ($widget)
    <div class="col-span-12 xl:col-span-4 lg:col-span-6">
        <div class="card h-full">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-info"><i class="ph ph-bell"></i></span>
                <h2 class="card-title">{{ __('notification::notification.widget.title') }}</h2>
                <a href="{{ route('admin.notification.index') }}" class="ms-auto text-sm">
                    {{ __('foundation::foundation.notification.view_all') }}
                </a>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-4">
                        <div class="fd-stat-value">{{ number_format($widget['total']) }}</div>
                        <div class="fd-stat-label">{{ __('notification::notification.widget.total') }}</div>
                    </div>
                    <div class="col-span-4">
                        <div class="fd-stat-value">{{ number_format($widget['unread']) }}</div>
                        <div class="fd-stat-label">{{ __('notification::notification.widget.unread') }}</div>
                    </div>
                    <div class="col-span-4">
                        <div class="fd-stat-value">{{ number_format($widget['push_subscribers']) }}</div>
                        <div class="fd-stat-label">{{ __('notification::notification.widget.push_subscribers') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
