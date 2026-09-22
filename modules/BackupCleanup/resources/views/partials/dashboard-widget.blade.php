@if ($widget)
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-info"><i class="ph-database"></i></span>
                <h6 class="card-title">{{ __('backupcleanup::backupcleanup.widget.title') }}</h6>
                @can('View Backup')
                    <a href="{{ route('admin.backups.index') }}" class="ms-auto fs-sm">
                        {{ __('backupcleanup::backupcleanup.widget.view_all') }}
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="fd-stat-value">{{ number_format($widget['count']) }}</div>
                <div class="fd-stat-label">{{ __('backupcleanup::backupcleanup.widget.files_label') }}</div>

                @if ($widget['latest'])
                    <div class="fd-overline mt-4 mb-1">{{ __('backupcleanup::backupcleanup.widget.last_backup') }}</div>
                    <ul class="fd-feed">
                        <li>
                            <span class="fd-icon-tile fd-icon-tile-sm is-neutral"><i class="ph-file-zip"></i></span>
                            <span class="fd-feed-body text-truncate">{{ $widget['latest']['filename'] }}</span>
                            <span class="fd-feed-meta">
                                {{ \Carbon\Carbon::createFromTimestamp($widget['latest']['date'])->diffForHumans(null, true, true) }}
                            </span>
                        </li>
                    </ul>
                @else
                    <div class="fd-empty py-4">
                        <span class="fd-empty-icon"><i class="ph-archive"></i></span>
                        <div class="fd-empty-title">{{ __('backupcleanup::backupcleanup.widget.none') }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
