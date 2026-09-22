@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-warning"><i class="ph-shield"></i></span>
                <h6 class="card-title">{{ __('rolepermission::rolepermission.layouts.label') }}</h6>
                @can('View Role')
                    <a href="{{ route('admin.role.index') }}" class="ms-auto fs-sm">
                        {{ __('rolepermission::rolepermission.widget.view_all') }}
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="fd-stat-value">{{ number_format($widget['total_roles']) }}</div>
                        <div class="fd-stat-label">{{ __('rolepermission::rolepermission.widget.roles_label') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="fd-stat-value">{{ number_format($widget['total_permissions']) }}</div>
                        <div class="fd-stat-label">{{ __('rolepermission::rolepermission.widget.permissions_label') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
