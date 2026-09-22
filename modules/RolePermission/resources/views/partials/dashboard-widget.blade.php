@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <i class="ph-shield me-2 text-warning"></i>
                <h6 class="card-title mb-0">{{ __('rolepermission::rolepermission.layouts.label') }}</h6>
                @can('View Role')
                    <a href="{{ route('admin.role.index') }}" class="ms-auto btn btn-sm btn-outline-warning">
                        {{ __('rolepermission::rolepermission.widget.view_all') }}
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-md-8">
                        <div class="fs-4 fw-bold text-warning">{{ number_format($widget['total_roles']) }}</div>
                        <div class="text-muted small">{{ __('rolepermission::rolepermission.widget.roles_label') }}</div>
                    </div>
                    <div class="col-md-8">
                        <div class="fs-4 fw-bold text-secondary">{{ number_format($widget['total_permissions']) }}</div>
                        <div class="text-muted small">{{ __('rolepermission::rolepermission.widget.permissions_label') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
