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
        {!! Form::label('search', 'Search', ['class' => 'form-label fs-sm']) !!}
        {!! Form::text('search', request('search'), ['class' => 'form-control form-control-sm', 'placeholder' => 'Recipient, subject…']) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('date_from', 'Date From', ['class' => 'form-label fs-sm']) !!}
        {!! Form::date('date_from', request('date_from'), ['class' => 'form-control form-control-sm']) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('date_to', 'Date To', ['class' => 'form-label fs-sm']) !!}
        {!! Form::date('date_to', request('date_to'), ['class' => 'form-control form-control-sm']) !!}
    </div>
    <div class="col-md-3 mb-2">
        {!! Form::label('status', 'Status', ['class' => 'form-label fs-sm']) !!}
        {!! Form::select('status', ['' => 'All Statuses', 'sent' => 'Sent', 'pending' => 'Pending', 'failed' => 'Failed'], request('status'), ['class' => 'form-control form-control-sm select', 'data-placeholder' => 'All']) !!}
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
            <td>{{ $log->sent_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
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
