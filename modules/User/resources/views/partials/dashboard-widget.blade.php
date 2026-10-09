@if ($widget)
    <div class="col-span-12 xl:col-span-4 lg:col-span-6 md:col-span-12">
        <div class="card h-full">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph ph-users-four"></i></span>
                <h2 class="card-title">{{ __('user::user.widget.title') }}</h2>
                @can('View User')
                    <a href="{{ route('admin.users.index') }}" class="ms-auto text-sm">
                        {{ __('user::user.widget.view_all') }}
                    </a>
                @endcan
            </div>
            <div class="card-body">
                {{-- Active users is a headline stat at the top of the page, not repeated here. --}}
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-6">
                        <div class="fd-stat-value">{{ number_format($widget['total_users']) }}</div>
                        <div class="fd-stat-label">{{ __('user::user.widget.total') }}</div>
                    </div>
                    <div class="col-span-6">
                        <div class="fd-stat-value">{{ number_format($widget['new_today']) }}</div>
                        <div class="fd-stat-label">{{ __('user::user.widget.new_today') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
