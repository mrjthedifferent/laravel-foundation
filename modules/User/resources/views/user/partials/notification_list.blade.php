<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header py-2 d-flex align-items-center gap-2 bg-body-tertiary border-bottom">
                <i class="ph-bell text-primary"></i>
                <span class="fw-semibold text-uppercase fs-xs">{{ __('user::user.notification_list.heading') }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-nowrap align-middle mb-0">
                        <thead class="table-active">
                        <tr>
                            <th>{{ __('user::user.notification_list.col_id') }}</th>
                            <th>{{ __('user::user.notification_list.col_title') }}</th>
                            <th>{{ __('user::user.notification_list.col_body') }}</th>
                            <th>{{ __('foundation::foundation.common.created_at') }}</th>
                            <th class="text-end">{{ __('foundation::foundation.common.action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($notifications as $notification)
                            <tr>
                                <td>{{ $notification->id }}</td>
                                <td>{{ $notification->title }}</td>
                                <td><x-truncated-text :text="$notification->body" :limit="50"/></td>
                                <td>{{ $notification->created_at->diffForHumans() }}</td>
                                <td class="text-end"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
