@extends('errorreport::layouts.master')
@section('breadcrumb')
<span class="breadcrumb-item active">All Errors</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-4 mb-2">
        <x-form.input name="search" label="Search" :value="request('search')" placeholder="Message, file, path…" />
    </div>
    <div class="col-md-4 mb-2">
        <x-form.input name="exception_class" label="Exception" :value="request('exception_class')" placeholder="Exception class" />
    </div>
     <div class="col-md-4 mb-2">
        <x-form.select name="resolved" label="Status" :options="['' => 'All', '0' => 'Unresolved', '1' => 'Resolved']" :selected="request('resolved')" />
    </div>
</x-search-card>

<x-table-view-pagination title="Error Reports" :data="$errorReports">
    <thead>
        <tr>
            <th>#</th>
            <th>Exception</th>
            <th>Message</th>
            <th>File</th>
            <th>Occurrences</th>
            <th>Last Seen</th>
            <th>Status</th>
            <th class="text-end">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($errorReports as $report)
        <tr>
            <td class="text-muted small">{{ $report->id }}</td>
            <td>
                <span class="fw-semibold">{{ class_basename($report->exception_class) }}</span>
            </td>
            <td style="max-width: 200px;">
                <x-truncated-text :text="$report->message" :limit="60" />
            </td>
            <td class="small font-monospace">
                {{ Str::limit(basename($report->file), 25) }}:{{ $report->line }}
            </td>
            <td>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ $report->occurrences }}</span>
            </td>
            <td class="text-nowrap small text-muted">
                {{ $report->last_seen_at->diffForHumans() }}
            </td>
            <td>
                @if ($report->isResolved())
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Resolved</span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Open</span>
                @endif
            </td>
            <td class="text-end">
                <x-dropdown-menu>
                    @can('View Error Report')
                    <x-dropdown-link :url="route('admin.error-reports.show', $report)">
                        <i class="ph-eye me-2"></i> View
                    </x-dropdown-link>
                    @endcan
                    @can('Resolve Error Report')
                    @if (! $report->isResolved())
                    <form action="{{ route('admin.error-reports.resolve', $report) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="ph-check-circle me-2"></i> Mark Resolved
                        </button>
                    </form>
                    @endif
                    @endcan
                    @can('Delete Error Report')
                    <button type="button" class="dropdown-item text-danger swal-delete"
                        data-url="{{ route('admin.error-reports.destroy', $report) }}"
                        data-text="Are you sure you want to delete this error report?">
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
