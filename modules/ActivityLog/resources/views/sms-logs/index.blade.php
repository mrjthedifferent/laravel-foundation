@extends('activitylog::layouts.master')
@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('activitylog::activitylog.sms_logs_index.breadcrumb') }}</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-3 mb-2">
        <x-form.input name="search" label="{{ __('foundation::foundation.common.search') }}" :value="request('search')" placeholder="{{ __('activitylog::activitylog.sms_logs_index.search_placeholder') }}" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.input name="date_from" label="{{ __('activitylog::activitylog.sms_logs_index.date_from') }}" type="date" :value="request('date_from')" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.input name="date_to" label="{{ __('activitylog::activitylog.sms_logs_index.date_to') }}" type="date" :value="request('date_to')" />
    </div>
    <div class="col-md-3 mb-2">
        <x-form.select class="select" name="status" label="{{ __('foundation::foundation.common.status') }}" :options="['' => __('activitylog::activitylog.sms_logs_index.all_statuses'), 'success' => __('activitylog::activitylog.sms_logs_index.success'), 'failed' => __('activitylog::activitylog.sms_logs_index.failed')]" :selected="request('status')" data-placeholder="{{ __('activitylog::activitylog.sms_logs_index.all') }}" />
    </div>
</x-search-card>

<x-table-view-pagination title="{{ __('activitylog::activitylog.sms_logs_index.breadcrumb') }}" :data="$smsLogs">
    <thead>
        <tr>
            <th>{{ __('activitylog::activitylog.sms_logs_index.col_id') }}</th>
            <th>{{ __('activitylog::activitylog.sms_logs_index.col_phone') }}</th>
            <th>{{ __('activitylog::activitylog.sms_logs_index.col_message') }}</th>
            <th>{{ __('foundation::foundation.common.status') }}</th>
            <th>{{ __('activitylog::activitylog.sms_logs_index.col_response') }}</th>
            <th>{{ __('activitylog::activitylog.sms_logs_index.col_sent_at') }}</th>
            @can('Delete SMS Log')
            <th class="text-end">{{ __('foundation::foundation.common.action') }}</th>
            @endcan
        </tr>
    </thead>
    <tbody>
        @foreach ($smsLogs as $log)
        <tr>
            <td>{{ $log->id }}</td>
            <td>{{ $log->phone }}</td>
            <td>
                <x-truncated-text :text="$log->message" :limit="60" />
            </td>
            <td>
                <span class="badge {{ $log->status === 'success' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                    {{ ucfirst($log->status) }}
                </span>
            </td>
            <td>
                @if ($log->response)
                <a href="#" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle view-response"
                    data-response="{{ e($log->response) }}">{{ __('foundation::foundation.common.view') }}</a>
                @else
                <span class="text-muted">—</span>
                @endif
            </td>
            <td>{{ $log->created_at->format(config('foundation.formats.datetime')) }}</td>
            @can('Delete SMS Log')
            <td class="text-end">
                <x-dropdown-menu>
                    <button type="button"
                        class="dropdown-item text-danger swal-delete"
                        data-url="{{ route('admin.sms-logs.destroy', $log->id) }}"
                        data-text="{{ __('activitylog::activitylog.sms_logs_index.delete_confirm') }}">
                        <i class="ph-trash me-2"></i> {{ __('foundation::foundation.common.delete') }}
                    </button>
                </x-dropdown-menu>
            </td>
            @endcan
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>

<x-modal id="response-modal" title="{{ __('activitylog::activitylog.sms_logs_index.gateway_response') }}">
    <pre id="response-content" class="mb-0" style="white-space: pre-wrap; word-break: break-all;"></pre>
</x-modal>
@endsection

@push('top_js')
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        $('.view-response').on('click', function(e) {
            e.preventDefault();
            const raw = $(this).data('response');
            let formatted;
            try {
                formatted = JSON.stringify(JSON.parse(raw), null, 2);
            } catch (err) {
                formatted = raw;
            }
            $('#response-content').text(formatted);
            $('#response-modal').modal('show');
        });
    });
</script>
@endpush