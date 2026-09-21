@extends('activitylog::layouts.master')
@section('breadcrumb')
<span class="breadcrumb-item active">SMS Logs</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-3 mb-2">
        {!! Form::label('search', 'Search', ['class' => 'form-label fs-sm']) !!}
        {!! Form::text('search', request('search'), ['class' => 'form-control form-control-sm', 'placeholder' => 'Phone, message…']) !!}
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
        {!! Form::select('status', ['' => 'All Statuses', 'success' => 'Success', 'failed' => 'Failed'], request('status'), ['class' => 'form-control form-control-sm select', 'data-placeholder' => 'All']) !!}
    </div>
</x-search-card>

<x-table-view-pagination title="SMS Logs" :data="$smsLogs">
    <thead>
        <tr>
            <th>ID</th>
            <th>Phone</th>
            <th>Message</th>
            <th>Status</th>
            <th>Response</th>
            <th>Sent At</th>
            @can('Delete SMS Log')
            <th class="text-end">Action</th>
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
                    data-response="{{ e($log->response) }}">View</a>
                @else
                <span class="text-muted">—</span>
                @endif
            </td>
            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
            @can('Delete SMS Log')
            <td class="text-end">
                <x-dropdown-menu>
                    <button type="button"
                        class="dropdown-item text-danger swal-delete"
                        data-url="{{ route('admin.sms-logs.destroy', $log->id) }}"
                        data-text="Are you sure you want to delete this log?">
                        <i class="ph-trash me-2"></i> Delete
                    </button>
                </x-dropdown-menu>
            </td>
            @endcan
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>

<x-modal id="response-modal" title="SMS Gateway Response">
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