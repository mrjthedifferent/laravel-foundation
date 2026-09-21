@extends('errorreport::layouts.master')
@section('breadcrumb')
<span class="breadcrumb-item active">Error #{{ $errorReport->id }}</span>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Error Report</h5>
        <div class="d-flex gap-2">
            @can('Resolve Error Report')
            @if (! $errorReport->isResolved())
            <form action="{{ route('admin.error-reports.resolve', $errorReport) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="ph-check-circle me-1"></i> Mark Resolved
                </button>
            </form>
            @endif
            @endcan
            @can('Delete Error Report')
            <button type="button" class="btn btn-danger btn-sm swal-delete"
                data-url="{{ route('admin.error-reports.destroy', $errorReport) }}"
                data-text="Are you sure you want to delete this error report?">
                <i class="ph-trash me-1"></i> Delete
            </button>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Exception:</strong>
                <code>{{ $errorReport->exception_class }}</code>
            </div>
            <div class="col-md-6">
                <strong>Status:</strong>
                @if ($errorReport->isResolved())
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Resolved</span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Open</span>
                @endif
            </div>
        </div>
        <div class="mb-3">
            <strong>Message:</strong>
            <p class="mb-0 mt-1">{{ $errorReport->message }}</p>
        </div>
        <div class="mb-3">
            <strong>Location:</strong>
            <code>{{ $errorReport->file }}:{{ $errorReport->line }}</code>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <strong>Occurrences:</strong> {{ $errorReport->occurrences }}
            </div>
            <div class="col-md-4">
                <strong>First seen:</strong> {{ $errorReport->first_seen_at->format(config('foundation.formats.datetime')) }}
            </div>
            <div class="col-md-4">
                <strong>Last seen:</strong> {{ $errorReport->last_seen_at->format(config('foundation.formats.datetime')) }}
            </div>
        </div>
        @if ($errorReport->request_url)
        <div class="mb-3">
            <strong>Request URL:</strong>
            <a href="{{ $errorReport->request_url }}" target="_blank" rel="noopener">{{ $errorReport->request_url }}</a>
        </div>
        @endif
        @if ($errorReport->request_method)
        <div class="mb-3">
            <strong>Request:</strong> {{ $errorReport->request_method }} {{ $errorReport->request_path }}
        </div>
        @endif
        @if ($errorReport->trace && count($errorReport->trace) > 0)
        <div class="mb-0">
            <strong>Stack Trace:</strong>
            <pre class="bg-dark text-light p-3 rounded mt-1 mb-0" style="max-height: 400px; overflow: auto; font-size: 12px;">@foreach ($errorReport->trace as $frame)
{{ ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '') }}()
    {{ ($frame['file'] ?? '') }}:{{ $frame['line'] ?? '' }}
@endforeach</pre>
        </div>
        @endif
    </div>
</div>
@endsection
