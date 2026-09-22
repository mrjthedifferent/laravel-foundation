@if ($widget)
    <div class="card h-100">
        <div class="card-header">
            <h6 class="card-title">{{ __('activitylog::activitylog.widget.recent_activity') }}</h6>
            @if (Route::has('admin.activity-logs.index') && auth()->user()->can('View Activity Log'))
                <a href="{{ route('admin.activity-logs.index') }}" class="ms-auto fs-sm">
                    {{ __('activitylog::activitylog.widget.view_all') }}
                </a>
            @endif
        </div>
        <div class="card-body py-1">
            @if ($widget['entries'])
                <ul class="fd-feed">
                    @foreach ($widget['entries'] as $entry)
                        @php
                            // Each label is its own __() call: the key must be findable in the source.
                            $tone = match ($entry['event']) {
                                'created' => ['is-success', 'ph-plus-circle', __('activitylog::activitylog.feed.event_created')],
                                'deleted' => ['is-danger', 'ph-trash', __('activitylog::activitylog.feed.event_deleted')],
                                'restored' => ['is-info', 'ph-arrow-counter-clockwise', __('activitylog::activitylog.feed.event_restored')],
                                default => ['', 'ph-pencil-simple', __('activitylog::activitylog.feed.event_updated')],
                            };
                        @endphp
                        <li>
                            <span class="fd-icon-tile fd-icon-tile-sm {{ $tone[0] }}"><i class="{{ $tone[1] }}"></i></span>
                            <div class="fd-feed-body">
                                @if ($entry['actor'])
                                    {{ __('activitylog::activitylog.feed.line', [
                                        'subject' => $entry['subject'],
                                        'event' => $tone[2],
                                        'actor' => $entry['actor'],
                                    ]) }}
                                @else
                                    {{ __('activitylog::activitylog.feed.line_system', [
                                        'subject' => $entry['subject'],
                                        'event' => $tone[2],
                                    ]) }}
                                @endif
                            </div>
                            <span class="fd-feed-meta">
                                {{ \Carbon\Carbon::parse($entry['at'])->diffForHumans(null, true, true) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="fd-empty">
                    <span class="fd-empty-icon"><i class="ph-clock-counter-clockwise"></i></span>
                    <p class="fd-empty-title">{{ __('activitylog::activitylog.feed.empty') }}</p>
                </div>
            @endif
        </div>
    </div>
@endif
