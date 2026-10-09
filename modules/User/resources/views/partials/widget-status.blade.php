<div class="card h-full">
    <div class="card-header">
        <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-chart-pie-slice"></i></span>
        <h2 class="card-title">{{ __('user::user.widget.status_title') }}</h2>
        @can('View User')
            <a href="{{ route('admin.users.index') }}" class="ms-auto text-sm">{{ __('user::user.widget.view_all') }}</a>
        @endcan
    </div>
    <div class="card-body">
        <x-chart-donut :parts="$parts" :label="__('user::user.widget.total')" />
    </div>
</div>
