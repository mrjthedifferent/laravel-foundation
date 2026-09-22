@extends('errorreport::layouts.master')
@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('errorreport::errorreport.index.breadcrumb') }}</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-4 mb-2">
        <x-form.input name="search" label="{{ __('foundation::foundation.common.search') }}" :value="request('search')" placeholder="{{ __('errorreport::errorreport.index.search_placeholder') }}" />
    </div>
    <div class="col-md-4 mb-2">
        <x-form.input name="exception_class" label="{{ __('errorreport::errorreport.index.exception_label') }}" :value="request('exception_class')" placeholder="{{ __('errorreport::errorreport.index.exception_placeholder') }}" />
    </div>
     <div class="col-md-4 mb-2">
        <x-form.select name="resolved" label="{{ __('foundation::foundation.common.status') }}" :options="['' => __('errorreport::errorreport.index.status_all'), '0' => __('errorreport::errorreport.index.status_unresolved'), '1' => __('errorreport::errorreport.index.status_resolved')]" :selected="request('resolved')" />
    </div>
</x-search-card>

<x-table-view-pagination title="{{ __('errorreport::errorreport.index.title') }}" :data="$errorReports">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('errorreport::errorreport.index.exception_label') }}</th>
            <th>{{ __('errorreport::errorreport.index.col_message') }}</th>
            <th>{{ __('errorreport::errorreport.index.col_file') }}</th>
            <th>{{ __('errorreport::errorreport.index.col_occurrences') }}</th>
            <th>{{ __('errorreport::errorreport.index.col_last_seen') }}</th>
            <th>{{ __('foundation::foundation.common.status') }}</th>
            <th class="text-end">{{ __('foundation::foundation.common.action') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($errorReports as $report)
        <tr>
            <td class="text-muted small">{{ $report->id }}</td>
            <td>
                <span class="fw-semibold">{{ class_basename($report->exception_class) }}</span>
            </td>
            <td class="w-sm">
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
                    <span class="fd-status is-success">{{ __('errorreport::errorreport.index.status_resolved') }}</span>
                @else
                    <span class="fd-status is-danger">{{ __('errorreport::errorreport.index.open') }}</span>
                @endif
            </td>
            <td class="text-end">
                <x-dropdown-menu>
                    @can('View Error Report')
                    <x-dropdown-link :url="route('admin.error-reports.show', $report)">
                        <i class="ph-eye"></i> {{ __('errorreport::errorreport.index.view') }}
                    </x-dropdown-link>
                    @endcan
                    @can('Resolve Error Report')
                    @if (! $report->isResolved())
                    <form action="{{ route('admin.error-reports.resolve', $report) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="ph-check-circle"></i> {{ __('errorreport::errorreport.index.mark_resolved') }}
                        </button>
                    </form>
                    @endif
                    @endcan
                    @can('Delete Error Report')
                    <button type="button" class="dropdown-item text-danger swal-delete"
                        data-url="{{ route('admin.error-reports.destroy', $report) }}"
                        data-text="{{ __('errorreport::errorreport.index.delete_confirm') }}">
                        <i class="ph-trash"></i> {{ __('errorreport::errorreport.index.delete') }}
                    </button>
                    @endcan
                </x-dropdown-menu>
            </td>
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>
@endsection
