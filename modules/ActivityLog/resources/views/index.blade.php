@php use Modules\ActivityLog\Helpers\ActivityLogHelper; @endphp
@extends('activitylog::layouts.master')
@section('breadcrumb')
<span class="breadcrumb-item active">Activity Logs</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-2 mb-2">
        {!! Form::label('search', 'Search', ['class' => 'form-label fs-sm']) !!}
        {!! Form::text('search', request('search'), ['class' => 'form-control form-control-sm', 'placeholder' => 'Values, IP, URL…']) !!}
    </div>
    <div class="col-md-2 mb-2">
        {!! Form::label('date_from', 'Date From', ['class' => 'form-label fs-sm']) !!}
        {!! Form::date('date_from', request('date_from'), ['class' => 'form-control form-control-sm']) !!}
    </div>
    <div class="col-md-2 mb-2">
        {!! Form::label('date_to', 'Date To', ['class' => 'form-label fs-sm']) !!}
        {!! Form::date('date_to', request('date_to'), ['class' => 'form-control form-control-sm']) !!}
    </div>
    <div class="col-md-2 mb-2">
        {!! Form::label('event', 'Event', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('event', ['' => 'All Events'] + array_combine($eventTypes, array_map('ucfirst', $eventTypes)), request('event'), ['class' => 'form-control form-control-sm select', 'data-placeholder' => 'All Events']) !!}
    </div>
    <div class="col-md-2 mb-2">
        {!! Form::label('auditable_type', 'Entity', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('auditable_type', ['' => 'All Entities'] + array_combine($auditableTypes, array_map(fn($t) => ActivityLogHelper::getModelName($t), $auditableTypes)), request('auditable_type'), ['class' => 'form-control form-control-sm select', 'data-placeholder' => 'All Entities']) !!}
    </div>
    <div class="col-md-2 mb-2">
        {!! Form::label('user_id', 'Action By', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('user_id', ['' => 'All Users'] + $users->mapWithKeys(fn($u) => [$u->id => $u->name])->toArray(), request('user_id'), ['class' => 'form-control form-control-sm select', 'data-placeholder' => 'All Users']) !!}
    </div>
</x-search-card>

<x-table-view-pagination title="Activity Logs" :data="$audits">
    <x-slot name="actions">
        <x-table-actions>
        </x-table-actions>
    </x-slot>

    <x-slot name="exports">
        @can('Export Activity Log')
        <x-table-export-dropdown>
            <x-table-export-item :href="route('admin.activity-logs.export').'?'.http_build_query(array_merge(request()->query(), ['format' => 'csv']))"
                class="swal-confirm"
                icon="ph-file-csv"
                title="CSV"
                data-text="Do you want to export activity logs to CSV? Large exports may take some time to process." />
            <x-table-export-item :href="route('admin.activity-logs.export').'?'.http_build_query(array_merge(request()->query(), ['format' => 'xlsx']))"
                class="swal-confirm"
                icon="ph-file-xls"
                title="Excel"
                data-text="Do you want to export activity logs to Excel? Large exports may take some time to process." />
            <x-table-export-item :href="route('admin.activity-logs.export').'?'.http_build_query(array_merge(request()->query(), ['format' => 'pdf']))"
                class="swal-confirm"
                icon="ph-file-pdf"
                title="PDF"
                data-text="Do you want to export activity logs to PDF? Large exports may take some time to process." />
        </x-table-export-dropdown>
        @endcan
    </x-slot>

    <thead>
        <tr>
            <th>#</th>
            <th>Event</th>
            <th>Description</th>
            <th>Action By</th>
            <th>IP Address</th>
            <th>URL</th>
            <th>Time</th>
            <th class="text-end">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($audits as $audit)
        @php
        $auditMetaData = $audit->getMetadata();
        $modifiedData = $audit->getModified();
        $model = ActivityLogHelper::getModelName($audit->auditable_type);
        $badgeColor = match($audit->event) {
        'created' => 'success',
        'updated' => 'primary',
        'deleted' => 'danger',
        'restored' => 'warning',
        'attach' => 'info',
        'detach' => 'secondary',
        'sync' => 'secondary',
        'impersonation_started', 'impersonation_ended' => 'dark',
        default => 'secondary',
        };
        $isPivotEvent = in_array($audit->event, ['attach', 'detach', 'sync']);
        @endphp
        <tr>
            <td class="text-muted small">{{ $audit->id }}</td>

            {{-- Event + Model --}}
            <td>
                <span class="badge bg-{{ $badgeColor }} mb-1">{{ ActivityLogHelper::titleCase($audit->event) }}</span>
                <div class="small fw-semibold">{{ $model }}</div>
                @if ($model === 'Setting' && $audit->auditable)
                <div class="small text-muted">{{ $audit->auditable->key }}</div>
                @endif
            </td>

            {{-- Description --}}
            <td style="max-width:280px;">
                @php
                $toStr = fn($v): string => match(true) {
                is_null($v) => '—',
                $v instanceof \BackedEnum => (string) $v->value,
                $v instanceof \UnitEnum => $v->name,
                is_object($v) && method_exists($v, '__toString') => (string) $v,
                is_array($v) => json_encode($v),
                default => (string) $v,
                };
                @endphp

                @if ($isPivotEvent)
                @foreach (array_unique(array_merge(array_keys($audit->old_values ?? []), array_keys($audit->new_values ?? []))) as $relation)
                @php
                $removed = $audit->old_values[$relation] ?? [];
                $added = $audit->new_values[$relation] ?? [];
                $sample = $removed[0] ?? $added[0] ?? [];
                $nk = collect(is_array($sample) ? array_keys($sample) : [])
                ->first(fn($k) => in_array($k, ['name','title','label','slug']), 'id');
                @endphp
                <div class="fs-sm mb-1">
                    <span class="fw-semibold text-uppercase fs-xs text-muted">{{ ActivityLogHelper::titleCase($relation) }}</span>
                    @if (!empty($added))
                    <div class="text-truncate">
                        <i class="ph-plus-circle text-success me-1"></i>{{ implode(', ', array_map(fn($i) => is_array($i) ? ($i[$nk] ?? $i['id'] ?? '?') : $toStr($i), $added)) }}
                    </div>
                    @endif
                    @if (!empty($removed))
                    <div class="text-truncate">
                        <i class="ph-minus-circle text-danger me-1"></i>{{ implode(', ', array_map(fn($i) => is_array($i) ? ($i[$nk] ?? $i['id'] ?? '?') : $toStr($i), $removed)) }}
                    </div>
                    @endif
                </div>
                @endforeach

                @else
                @php
                $isUpdated = $audit->event === 'updated';
                $values = $isUpdated ? $modifiedData : ($audit->event === 'deleted' ? ($audit->old_values ?? []) : ($audit->new_values ?? []));
                @endphp
                @forelse ($values as $key => $value)
                <div class="text-truncate fs-sm">
                    <strong class="text-body">{{ ActivityLogHelper::titleCase($key) }}:</strong>
                    @if ($isUpdated && is_array($value) && array_key_exists('old', $value) && array_key_exists('new', $value))
                    <span class="text-danger">{{ Str::limit($toStr($value['old']), 20) }}</span>
                    <i class="ph-arrow-right mx-1 text-muted"></i>
                    <span class="text-success">{{ Str::limit($toStr($value['new']), 20) }}</span>
                    @else
                    <span class="text-muted">{{ Str::limit($toStr($value), 30) }}</span>
                    @endif
                </div>
                @empty
                <span class="text-muted fst-italic fs-xs">—</span>
                @endforelse
                @endif
            </td>

            {{-- Action By — direct link to user profile --}}
            <td>
                @if ($audit->user_id !== null)
                @if ($audit->user)
                @can('view', $audit->user)
                <a href="{{ route('admin.users.show', $audit->user_id) }}"
                    class="d-flex align-items-center gap-2 text-decoration-none user-link"
                    title="View user profile">
                    <img src="{{ $audit->user->image }}"
                        alt="{{ $audit->user->name }}"
                        class="rounded-circle"
                        width="28" height="28"
                        style="object-fit:cover; flex-shrink:0;">
                    <span>
                        <span class="d-block fw-semibold text-body lh-sm fs-sm">
                            {{ $audit->user->name }}
                        </span>
                        @if ($audit->user->roles->isNotEmpty())
                        <span class="badge bg-body-tertiary text-muted border fs-xs">
                            {{ $audit->user->roles->first()->name }}
                        </span>
                        @endif
                    </span>
                </a>
                @else
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ $audit->user->image }}"
                        alt="{{ $audit->user->name }}"
                        class="rounded-circle"
                        width="28" height="28"
                        style="object-fit:cover; flex-shrink:0;">
                    <span class="fw-semibold fs-sm">{{ $audit->user->name }}</span>
                </div>
                @endcan
                @else
                <span class="text-muted small">
                    <i class="ph-user-circle me-1"></i>Deleted User #{{ $audit->user_id }}
                </span>
                @endif
                @else
                <span class="text-muted small">
                    <i class="ph-robot me-1"></i>System
                </span>
                @endif
                @if ($impersonatedId = ActivityLogHelper::impersonatedUserId($audit->tags))
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-xs mt-1"
                    title="Done while impersonating this user">
                    <i class="ph-user-switch me-1"></i>as {{ ($impersonatedUsers ?? collect())[$impersonatedId] ?? 'User #'.$impersonatedId }}
                </span>
                @endif
            </td>

            {{-- IP --}}
            <td>
                <a href="#" class="badge bg-body-tertiary text-muted border font-monospace fs-xs track-ip">
                    {{ $auditMetaData['audit_ip_address'] }}
                </a>
            </td>

            {{-- URL --}}
            <td>
                <x-truncated-text :text="$auditMetaData['audit_url']" :limit="20" />
            </td>

            {{-- Time --}}
            <td class="text-nowrap">
                <span class="small text-muted" title="{{ $audit->created_at->format('Y-m-d H:i:s') }}">
                    {{ $audit->created_at->diffForHumans() }}
                </span>
            </td>

            {{-- Actions --}}
            <td class="text-end">
                <x-dropdown-menu>
                    @can('View Activity Log')
                    <x-dropdown-link :url="route('admin.activity-logs.show', $audit->id)">
                        <i class="ph-eye me-2"></i> View Content History
                    </x-dropdown-link>
                    @endcan
                    @if ($audit->user_id && $audit->user)
                    @can('view', $audit->user)
                    <x-dropdown-link :url="route('admin.users.show', $audit->user_id)">
                        <i class="ph-user me-2"></i> View User Profile
                    </x-dropdown-link>
                    @endcan
                    @endif
                    @can('Delete Activity Log')
                    <button type="button" class="dropdown-item text-danger swal-delete"
                        data-url="{{ route('admin.activity-logs.destroy', $audit->id) }}"
                        data-text="Are you sure you want to delete this log?">
                        <i class="ph-trash me-2"></i> Delete Log
                    </button>
                    @endcan
                </x-dropdown-menu>
            </td>
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>

<x-modal id="track-ip-modal" title="IP Information">
    <div class="row">
        <div class="col-md-12" id="ip-details"></div>
    </div>
</x-modal>
@endsection

@push('top_js')
@endpush



@push('scripts')
<script>
    $(document).ready(function() {
        $('.track-ip').on('click', function(e) {
            e.preventDefault();
            const ip = $(this).text().trim();
            $('#track-ip-modal').modal('show');
            $('#ip-details').html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading IP information...</p></div>');
            $.ajax({
                url: "{{ route('admin.track-ip') }}",
                type: 'GET',
                data: {
                    ip: ip
                },
                success: function(data) {
                    $('#ip-details').html(data);
                },
                error: function() {
                    $('#ip-details').html('<div class="alert alert-danger">Failed to load IP information.</div>');
                }
            });
        });
    });
</script>
@endpush