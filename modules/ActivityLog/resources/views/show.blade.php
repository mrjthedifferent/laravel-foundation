@php use Modules\ActivityLog\Helpers\ActivityLogHelper;$helper = ActivityLogHelper::class; @endphp
@extends('activitylog::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">Content History</span>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-0">
                    Content History
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-2">{{ $helper::getModelName($audit->auditable_type) }}</span>
                </h5>
                <small class="text-muted">{{ $audits->total() }} record(s)</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="text-muted mb-0 small">Per page:</label>
                    <select name="per_page" class="form-select form-select-sm" style="width:75px;"
                            onchange="this.form.submit()">
                        @foreach ([10, 20, 50, 100] as $pp)
                            <option
                                value="{{ $pp }}" {{ request()->integer('per_page', 20) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ph-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="table-active">
                <tr>
                    <th style="width:95px;">Event</th>
                    <th style="width:140px;">Action By</th>
                    <th style="width:140px;">Time</th>
                    <th style="width:120px;">IP / URL</th>
                    <th>Changes</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($audits as $audit)
                    @php
                        $isPivot    = in_array($audit->event, ['attach', 'detach', 'sync']);
                        $badgeColor = match($audit->event) {
                            'created'  => 'bg-success-subtle text-success border border-success-subtle',
                            'updated'  => 'bg-primary-subtle text-primary border border-primary-subtle',
                            'deleted'  => 'bg-danger-subtle text-danger border border-danger-subtle',
                            'restored' => 'bg-warning-subtle text-warning border border-warning-subtle',
                            'attach'   => 'bg-info-subtle text-info border border-info-subtle',
                            'detach'   => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                            'sync'     => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                            default    => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                        };
                    @endphp
                    <tr class="align-top">

                        {{-- Event --}}
                        <td>
                            <span class="badge {{ $badgeColor }}">{{ ucfirst($audit->event) }}</span>
                        </td>

                        {{-- Action By --}}
                        <td>
                            @if ($audit->user)
                                @can('view', $audit->user)
                                    <a href="{{ route('admin.users.show', $audit->user_id) }}"
                                       class="d-flex align-items-center gap-1 text-decoration-none">
                                        <img src="{{ $audit->user->image }}" class="rounded-circle"
                                             width="20" height="20" style="object-fit:cover;" alt="">
                                        <span class="text-body small">{{ $audit->user->name }}</span>
                                    </a>
                                @else
                                    <span class="d-flex align-items-center gap-1">
                                            <img src="{{ $audit->user->image }}" class="rounded-circle"
                                                 width="20" height="20" style="object-fit:cover;" alt="">
                                            <span class="small">{{ $audit->user->name }}</span>
                                        </span>
                                @endcan
                            @elseif ($audit->user_id)
                                <span class="text-muted small">Deleted #{{ $audit->user_id }}</span>
                            @else
                                <span class="text-muted small">System</span>
                            @endif
                        </td>

                        {{-- Time --}}
                        <td class="text-muted small text-nowrap">
                            {{ $audit->created_at->format('Y-m-d H:i:s') }}
                        </td>

                        {{-- IP / URL --}}
                        <td class="small">
                            @if ($audit->ip_address)
                                <a href="#" class="track-ip text-muted font-monospace d-block"
                                   data-ip="{{ $audit->ip_address }}">{{ $audit->ip_address }}</a>
                            @endif
                            @if ($audit->url)
                                <a href="{{ $audit->url }}" class="text-muted d-block text-truncate"
                                   style="max-width:110px;" title="{{ $audit->url }}"
                                   target="_blank" rel="noopener">{{ $audit->url }}</a>
                            @endif
                        </td>

                        {{-- Changes — full inline, no truncation --}}
                        <td>
                            @if ($isPivot)
                                @php
                                    $allRelations = array_unique(array_merge(
                                        array_keys($audit->old_values ?? []),
                                        array_keys($audit->new_values ?? [])
                                    ));
                                @endphp
                                @foreach ($allRelations as $rel)
                                    @php
                                        $removed = $audit->old_values[$rel] ?? [];
                                        $added   = $audit->new_values[$rel] ?? [];
                                        $sample  = $removed[0] ?? $added[0] ?? [];
                                        $nk      = collect(is_array($sample) ? array_keys($sample) : [])
                                                       ->first(fn($k) => in_array($k, ['name','title','label','slug']), 'id');
                                    @endphp
                                    <div class="mb-1">
                                        <span
                                            class="fw-semibold text-uppercase text-muted fs-xs me-1">{{ $helper::titleCase($rel) }}</span>
                                        @foreach ($added as $item)
                                            <span
                                                class="badge bg-success-subtle text-success border border-success-subtle font-monospace">+ {{ is_array($item) ? ($item[$nk] ?? $item['id'] ?? '?') : $item }}</span>
                                        @endforeach
                                        @foreach ($removed as $item)
                                            <span
                                                class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace">- {{ is_array($item) ? ($item[$nk] ?? $item['id'] ?? '?') : $item }}</span>
                                        @endforeach
                                    </div>
                                @endforeach

                            @elseif ($audit->event === 'updated')
                                @forelse ($audit->old_values as $attr => $old)
                                    @php
                                        $oldVal = is_array($old) ? json_encode($old) : ($old ?? 'N/A');
                                        $newVal = isset($audit->new_values[$attr])
                                            ? (is_array($audit->new_values[$attr]) ? json_encode($audit->new_values[$attr]) : $audit->new_values[$attr])
                                            : 'N/A';
                                    @endphp
                                    <div
                                        class="d-flex flex-wrap align-items-baseline gap-1 py-1 border-bottom border-opacity-10 fs-sm">
                                        <span class="fw-semibold text-uppercase text-muted fs-xs flex-shrink-0"
                                              style="min-width:90px;">{{ $helper::titleCase($attr) }}</span>
                                        <code
                                            class="text-danger bg-danger bg-opacity-10 px-1 rounded">{{ $oldVal }}</code>
                                        <i class="ph-arrow-right text-muted fs-xs"></i>
                                        <code
                                            class="text-success bg-success bg-opacity-10 px-1 rounded">{{ $newVal }}</code>
                                    </div>
                                @empty
                                    <span class="text-muted small fst-italic">No changes recorded.</span>
                                @endforelse

                            @elseif (in_array($audit->event, ['created', 'restored']))
                                @foreach ($audit->new_values as $attr => $val)
                                    <div
                                        class="d-flex flex-wrap align-items-baseline gap-1 py-1 border-bottom border-opacity-10 fs-sm">
                                        <span class="fw-semibold text-uppercase text-muted fs-xs flex-shrink-0"
                                              style="min-width:90px;">{{ $helper::titleCase($attr) }}</span>
                                        <code
                                            class="text-success bg-success bg-opacity-10 px-1 rounded">{{ is_array($val) ? json_encode($val) : $val }}</code>
                                    </div>
                                @endforeach

                            @elseif ($audit->event === 'deleted')
                                @foreach ($audit->old_values as $attr => $val)
                                    <div
                                        class="d-flex flex-wrap align-items-baseline gap-1 py-1 border-bottom border-opacity-10 fs-sm">
                                        <span class="fw-semibold text-uppercase text-muted fs-xs flex-shrink-0"
                                              style="min-width:90px;">{{ $helper::titleCase($attr) }}</span>
                                        <code
                                            class="text-danger bg-danger bg-opacity-10 px-1 rounded">{{ is_array($val) ? json_encode($val) : $val }}</code>
                                    </div>
                                @endforeach

                            @else
                                <span class="text-muted small fst-italic">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No audit history available.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($audits->hasPages())
            <div class="card-footer d-flex justify-content-end">
                {{ $audits->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <x-modal id="track-ip-modal" title="IP Information">
        <div id="ip-details"></div>
    </x-modal>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $(document).on('click', '.track-ip', function (e) {
                e.preventDefault();
                const ip = $(this).data('ip') || $(this).text().trim();
                $('#track-ip-modal').modal('show');
                $('#ip-details').html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>');
                $.ajax({
                    url: "{{ route('admin.track-ip') }}",
                    data: {ip},
                    success: data => $('#ip-details').html(data),
                    error: () => $('#ip-details').html('<div class="alert alert-danger mb-0">Failed to load IP information.</div>'),
                });
            });
        });
    </script>
@endpush
