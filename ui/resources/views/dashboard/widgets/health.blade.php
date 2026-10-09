<div class="card h-full">
    <div class="card-header">
        <span class="fd-icon-tile fd-icon-tile-sm {{ $problems ? 'is-warning' : 'is-success' }}"><i class="ph-heartbeat"></i></span>
        <h2 class="card-title">{{ __('foundation::foundation.dashboard.widget_health') }}</h2>
        <span class="badge {{ $problems ? 'badge-warning' : 'badge-success' }} ms-auto">
            {{ $problems ? trans_choice('foundation::foundation.dashboard.health_needs_attention', $problems, ['count' => $problems]) : __('foundation::foundation.dashboard.health_all_good') }}
        </span>
    </div>
    <div class="card-body py-1">
        <ul class="fd-health">
            @foreach ($checks as $check)
                @php
                    $tone = match ($check['status']) {
                        'fail' => ['is-danger', 'ph-x-circle'],
                        'warn' => ['is-warning', 'ph-warning'],
                        default => ['is-success', 'ph-check-circle'],
                    };
                @endphp
                <li>
                    <span class="fd-health-icon {{ $tone[0] }}"><i class="{{ $tone[1] }}"></i></span>
                    <div class="fd-health-body">
                        @if (! empty($check['href']))
                            <a href="{{ $check['href'] }}" class="fd-health-label">{{ $check['label'] }}</a>
                        @else
                            <span class="fd-health-label">{{ $check['label'] }}</span>
                        @endif
                        @if (! empty($check['detail']))
                            <span class="fd-health-detail">{{ $check['detail'] }}</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
