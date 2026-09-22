@extends('activitylog::layouts.master')
@section('breadcrumb')
<span class="breadcrumb-item active">Email Logs</span>
@endsection

@php
    $statusBadge = ['sent' => 'success', 'pending' => 'warning', 'failed' => 'danger'];
@endphp

@section('content')
<x-search-card>
    <div class="col-md-3 mb-2">
        <x-form.input name="search" label="Search" :value="request('search')" placeholder="Recipient, subject…" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.input name="date_from" label="Date From" type="date" :value="request('date_from')" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.input name="date_to" label="Date To" type="date" :value="request('date_to')" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.select class="select" name="status" label="Status" :options="['' => 'All Statuses', 'sent' => 'Sent', 'pending' => 'Pending', 'failed' => 'Failed']" :selected="request('status')" data-placeholder="All" />
    </div>
</x-search-card>

<x-table-view-pagination title="Email Logs" :data="$emailLogs">
    <thead>
        <tr>
            <th>ID</th>
            <th>To</th>
            <th>Subject</th>
            <th>Notification</th>
            <th>Status</th>
            <th>Sent At</th>
            <th class="text-end">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($emailLogs as $log)
        <tr>
            <td>{{ $log->id }}</td>
            <td>
                {{ $log->to_email }}
                @if ($log->to_name)
                <div class="text-muted fs-xs">{{ $log->to_name }}</div>
                @endif
            </td>
            <td>
                <x-truncated-text :text="$log->subject ?? '—'" :limit="50" />
            </td>
            <td>
                @if ($log->notification)
                <span class="text-muted fs-xs">{{ class_basename($log->notification) }}</span>
                @else
                <span class="text-muted">—</span>
                @endif
            </td>
            <td>
                <span class="badge bg-{{ $statusBadge[$log->status] ?? 'secondary' }}">
                    {{ ucfirst($log->status) }}
                </span>
            </td>
            <td>{{ $log->sent_at?->format(config('foundation.formats.datetime')) ?? '—' }}</td>
            <td class="text-end">
                <x-dropdown-menu>
                    <x-dropdown-link :url="route('admin.email-logs.show', $log->id)">
                        <i class="ph-eye me-2"></i> View
                    </x-dropdown-link>
                    @can('Delete Email Log')
                    <div class="dropdown-divider"></div>
                    <button type="button"
                        class="dropdown-item text-danger swal-delete"
                        data-url="{{ route('admin.email-logs.destroy', $log->id) }}"
                        data-text="Are you sure you want to delete this log?">
                        <i class="ph-trash me-2"></i> Delete
                    </button>
                    @endcan
                </x-dropdown-menu>
            </td>
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>
@endsection

@push('top_js')
@endpush
