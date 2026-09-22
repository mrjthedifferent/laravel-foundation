@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <i class="ph-users-four me-2 text-primary"></i>
                <h6 class="card-title mb-0">{{ __('user::user.widget.title') }}</h6>
                @can('View User')
                    <a href="{{ route('admin.users.index') }}" class="ms-auto btn btn-sm btn-outline-primary">
                        {{ __('user::user.widget.view_all') }}
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold">{{ number_format($widget['total_users']) }}</div>
                        <div class="text-muted small">{{ __('user::user.widget.total') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold text-success">{{ number_format($widget['active_users']) }}</div>
                        <div class="text-muted small">{{ __('foundation::foundation.common.active') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fs-4 fw-bold text-info">{{ number_format($widget['new_today']) }}</div>
                        <div class="text-muted small">{{ __('user::user.widget.new_today') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
