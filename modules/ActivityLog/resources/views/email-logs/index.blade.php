@extends('activitylog::layouts.master')
@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('activitylog::activitylog.email_logs_index.breadcrumb') }}</span>
@endsection

@php
    $statusBadge = ['sent' => 'success', 'pending' => 'warning', 'failed' => 'danger'];
@endphp

@section('content')
<x-search-card>
    <div class="col-md-3 mb-2">
        <x-form.input name="search" label="{{ __('foundation::foundation.common.search') }}" :value="request('search')" placeholder="{{ __('activitylog::activitylog.email_logs_index.search_placeholder') }}" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.input name="date_from" label="{{ __('activitylog::activitylog.email_logs_index.date_from') }}" type="date" :value="request('date_from')" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.input name="date_to" label="{{ __('activitylog::activitylog.email_logs_index.date_to') }}" type="date" :value="request('date_to')" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.select class="select" name="status" label="{{ __('foundation::foundation.common.status') }}" :options="['' => __('activitylog::activitylog.email_logs_index.all_statuses'), 'sent' => __('activitylog::activitylog.email_logs_index.sent'), 'pending' => __('activitylog::activitylog.email_logs_index.pending'), 'failed' => __('activitylog::activitylog.email_logs_index.failed')]" :selected="request('status')" data-placeholder="{{ __('activitylog::activitylog.email_logs_index.all') }}" />
    </div>
</x-search-card>

<x-table-view-pagination title="{{ __('activitylog::activitylog.email_logs_index.breadcrumb') }}" :data="$emailLogs">
    <thead>
        <tr>
            <th>{{ __('activitylog::activitylog.email_logs_index.col_id') }}</th>
            <th>{{ __('activitylog::activitylog.email_logs_index.col_to') }}</th>
            <th>{{ __('activitylog::activitylog.email_logs_index.col_subject') }}</th>
            <th>{{ __('activitylog::activitylog.email_logs_index.col_notification') }}</th>
            <th>{{ __('foundation::foundation.common.status') }}</th>
            <th>{{ __('activitylog::activitylog.email_logs_index.col_sent_at') }}</th>
            <th class="text-end">{{ __('foundation::foundation.common.action') }}</th>
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
                        <i class="ph-eye me-2"></i> {{ __('foundation::foundation.common.view') }}
                    </x-dropdown-link>
                    @can('Delete Email Log')
                    <div class="dropdown-divider"></div>
                    <button type="button"
                        class="dropdown-item text-danger swal-delete"
                        data-url="{{ route('admin.email-logs.destroy', $log->id) }}"
                        data-text="{{ __('activitylog::activitylog.email_logs_index.delete_confirm') }}">
                        <i class="ph-trash me-2"></i> {{ __('foundation::foundation.common.delete') }}
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
