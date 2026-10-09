@if ($widget)
    <div class="col-span-12 xl:col-span-4 lg:col-span-6">
        <div class="card h-full">
            <div class="card-header">
                <span class="fd-icon-tile fd-icon-tile-sm is-info"><i class="ph-database"></i></span>
                <h2 class="card-title">{{ __('backupcleanup::backupcleanup.widget.title') }}</h2>
                @can('View Backup')
                    <a href="{{ route('admin.backups.index') }}" class="ms-auto text-sm">
                        {{ __('backupcleanup::backupcleanup.widget.view_all') }}
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="fd-stat-value">{{ number_format($widget['count']) }}</div>
                <div class="fd-stat-label">{{ __('backupcleanup::backupcleanup.widget.files_label') }}</div>

                @if ($widget['latest'])
                    <div class="fd-overline mt-6 mb-1">{{ __('backupcleanup::backupcleanup.widget.last_backup') }}</div>
                    <ul class="fd-feed">
                        <li>
                            <span class="fd-icon-tile fd-icon-tile-sm is-neutral"><i class="ph-file-zip"></i></span>
                            <span class="fd-feed-body truncate">{{ $widget['latest']['filename'] }}</span>
                            <span class="fd-feed-meta">
                                {{ \Carbon\Carbon::createFromTimestamp($widget['latest']['date'])->diffForHumans(null, true, true) }}
                            </span>
                        </li>
                    </ul>
                @else
                    <div class="fd-empty py-6">
                        <span class="fd-empty-icon"><i class="ph-archive"></i></span>
                        <div class="fd-empty-title">{{ __('backupcleanup::backupcleanup.widget.none') }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
