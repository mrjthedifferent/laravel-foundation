@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-users-four"></i></span>
                <h2 class="card-title">{{ __('user::user.widget.title') }}</h2>
                @can('View User')
                    <a href="{{ route('admin.users.index') }}" class="ms-auto fs-sm">
                        {{ __('user::user.widget.view_all') }}
                    </a>
                @endcan
            </div>
            <div class="card-body">
                {{-- Active users is a headline stat at the top of the page, not repeated here. --}}
                <div class="row g-3">
                    <div class="col-6">
                        <div class="fd-stat-value">{{ number_format($widget['total_users']) }}</div>
                        <div class="fd-stat-label">{{ __('user::user.widget.total') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="fd-stat-value">{{ number_format($widget['new_today']) }}</div>
                        <div class="fd-stat-label">{{ __('user::user.widget.new_today') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
