@if ($widget)
    <div class="col-span-12 xl:col-span-4 lg:col-span-6 md:col-span-12">
        <div class="card h-full">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-warning"><i class="ph ph-shield"></i></span>
                <h2 class="card-title">{{ __('rolepermission::rolepermission.layouts.label') }}</h2>
                @can('View Role')
                    <a href="{{ route('admin.role.index') }}" class="ms-auto text-sm">
                        {{ __('rolepermission::rolepermission.widget.view_all') }}
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-6">
                        <div class="fd-stat-value">{{ number_format($widget['total_roles']) }}</div>
                        <div class="fd-stat-label">{{ __('rolepermission::rolepermission.widget.roles_label') }}</div>
                    </div>
                    <div class="col-span-6">
                        <div class="fd-stat-value">{{ number_format($widget['total_permissions']) }}</div>
                        <div class="fd-stat-label">{{ __('rolepermission::rolepermission.widget.permissions_label') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
