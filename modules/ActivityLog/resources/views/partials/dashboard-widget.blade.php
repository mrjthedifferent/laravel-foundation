@if ($widget)
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <i class="ph-activity me-2 text-success"></i>
                <h6 class="card-title mb-0">{{ __('activitylog::activitylog.widget.title') }}</h6>
                @if (auth()->user()->hasAnyPermission(['View Activity Log']))
                    <a href="{{ route('admin.activity-logs.index') }}" class="ms-auto btn btn-sm btn-outline-success">
                        {{ __('activitylog::activitylog.widget.view_all') }}
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3 text-center mb-3">
                    <div class="col-md-8">
                        <div class="fs-4 fw-bold text-success">{{ number_format($widget['activities_today']) }}</div>
                        <div class="text-muted small">{{ __('activitylog::activitylog.widget.activities_today') }}</div>
                    </div>
                    <div class="col-md-8">
                        <div class="fs-4 fw-bold text-warning">{{ number_format($widget['sms_logs']) }}</div>
                        <div class="text-muted small">{{ __('activitylog::activitylog.widget.sms_logs') }}</div>
                    </div>
                </div>

                @if ($widget['recent_audits']->isNotEmpty())
                    <div class="border-top pt-2">
                        <div class="text-muted small fw-semibold mb-2 text-uppercase">{{ __('activitylog::activitylog.widget.recent_activity') }}</div>
                        @foreach ($widget['recent_audits'] as $audit)
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span
                                    class="badge bg-secondary bg-opacity-10 text-secondary text-capitalize">{{ $audit->event }}</span>
                                <span class="text-muted small text-truncate flex-grow-1">
                                    {{ class_basename($audit->auditable_type) }}
                                </span>
                                <span class="text-muted small text-nowrap">
                                    {{ \Carbon\Carbon::parse($audit->created_at)->diffForHumans(null, true, true) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
