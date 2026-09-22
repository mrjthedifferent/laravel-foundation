@if ($widget)
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-success"><i class="ph-activity"></i></span>
                <h6 class="card-title">{{ __('activitylog::activitylog.widget.title') }}</h6>
                @if (auth()->user()->can('View Activity Log'))
                    <a href="{{ route('admin.activity-logs.index') }}" class="ms-auto fs-sm">
                        {{ __('activitylog::activitylog.widget.view_all') }}
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="fd-stat-value">{{ number_format($widget['activities_today']) }}</div>
                        <div class="fd-stat-label">{{ __('activitylog::activitylog.widget.activities_today') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="fd-stat-value">{{ number_format($widget['sms_logs']) }}</div>
                        <div class="fd-stat-label">{{ __('activitylog::activitylog.widget.sms_logs') }}</div>
                    </div>
                </div>

                @if (! empty($widget['recent_audits']))
                    <div class="fd-overline mt-4 mb-1">{{ __('activitylog::activitylog.widget.recent_activity') }}</div>
                    <ul class="fd-feed">
                        @foreach ($widget['recent_audits'] as $audit)
                            <li>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis text-capitalize">{{ $audit['event'] }}</span>
                                <span class="fd-feed-body text-truncate">{{ \Modules\ActivityLog\Helpers\ActivityLogHelper::titleCase(class_basename($audit['auditable_type'])) }}</span>
                                <span class="fd-feed-meta">
                                    {{ $audit['created_at'] ? \Carbon\Carbon::parse($audit['created_at'])->diffForHumans(null, true, true) : '' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endif
