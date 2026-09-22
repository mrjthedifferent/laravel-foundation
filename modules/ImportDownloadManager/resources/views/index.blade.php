@php
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
@endphp
@extends('importdownloadmanager::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('importdownloadmanager::importdownloadmanager.index.breadcrumb') }}</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-4 mb-2">
        <x-form.input name="search" label="{{ __('foundation::foundation.common.search') }}" :value="request('search')" placeholder="{{ __('importdownloadmanager::importdownloadmanager.index.search_placeholder') }}" />
    </div>
    <div class="col-md-4 mb-2">
        <x-form.select class="select" name="type" label="{{ __('importdownloadmanager::importdownloadmanager.index.type') }}" :options="$types" :selected="request('type')" data-placeholder="{{ __('importdownloadmanager::importdownloadmanager.index.all_types') }}" />
    </div>
    <div class="col-md-4 mb-2">
        <x-form.select class="select" name="status" label="{{ __('foundation::foundation.common.status') }}" :options="$statuses" :selected="request('status')" data-placeholder="{{ __('importdownloadmanager::importdownloadmanager.index.all_statuses') }}" />
    </div>
</x-search-card>

<x-table-view-pagination title="{{ __('importdownloadmanager::importdownloadmanager.index.title') }}" :data="$downloadImports" empty-icon="ph-tray" empty-message="{{ __('importdownloadmanager::importdownloadmanager.index.empty') }}">
    <x-slot name="actions">
        <x-table-actions>
            <x-table-action :href="route('admin.download.import.manager.index')" class="btn-light" icon="ph-arrows-clockwise" title="{{ __('importdownloadmanager::importdownloadmanager.index.refresh') }}" />
        </x-table-actions>
    </x-slot>

    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('importdownloadmanager::importdownloadmanager.index.col_date') }}</th>
            <th>{{ __('importdownloadmanager::importdownloadmanager.index.col_title') }}</th>
            <th>{{ __('foundation::foundation.common.status') }}</th>
            <th>{{ __('importdownloadmanager::importdownloadmanager.index.type') }}</th>
            <th>{{ __('importdownloadmanager::importdownloadmanager.index.col_remarks') }}</th>
            @canany(['Import Manager Data Download', 'Import Manager Data Delete'])
            <th class="text-end">{{ __('foundation::foundation.common.action') }}</th>
            @endcanany
        </tr>
    </thead>
    <tbody>
        @php
        $i = $downloadImports->toArray()['from'] ?? 1;
        @endphp
        @foreach ($downloadImports as $item)
        @if (!in_array($item->status->value, ['completed', 'failed']))
        <input type="hidden" name="ids[]" value="{{ $item->id }}">
        @endif
        <tr>
            <td>{{ $i++ }}</td>
            <td class="fs-sm text-muted">{{ date('Y-m-d H:i', strtotime($item->created_at)) }}</td>
            <td>{{ display_label($item->title) }}</td>
            <td>
                @php
                $statusColor = match($item->status) {
                ImportStatus::Pending => 'bg-info-subtle text-info border border-info-subtle',
                ImportStatus::Processing => 'bg-warning-subtle text-warning border border-warning-subtle',
                ImportStatus::Failed => 'bg-danger-subtle text-danger border border-danger-subtle',
                ImportStatus::Completed => 'bg-success-subtle text-success border border-success-subtle',
                default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                };
                @endphp
                <span id="status{{ $item->id }}" class="badge {{ $statusColor }}">
                    {{ $item->status->value }}
                </span>
            </td>
            <td>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $item->type->value }}</span>
            </td>
            <td>
                {{-- remarks may hold an exception message or another job's output; it is
                     never trusted HTML, so it is escaped before the newline breaks are added. --}}
                <div id="remarks{{ $item->id }}">{!! nl2br(e($item->remarks)) !!}</div>
            </td>
            @canany(['Import Manager Data Download', 'Import Manager Data Delete'])
            <td class="text-end">
                <x-dropdown-menu>
                    @can('Import Manager Data Download')
                    @if ($item->status === ImportStatus::Completed && $item->type === ImportType::Download)
                    <x-dropdown-link :url="route('admin.download.import.manager.download', ['downloadImportManager' => $item->id])">
                        <i class="ph-download-simple"></i> {{ __('importdownloadmanager::importdownloadmanager.index.download') }}
                    </x-dropdown-link>
                    @endif
                    @if ($item->type !== ImportType::Download)
                    <x-dropdown-link :url="route('admin.download.import.manager.download', ['downloadImportManager' => $item->id])">
                        <i class="ph-download-simple"></i> {{ __('importdownloadmanager::importdownloadmanager.index.download_source_file') }}
                    </x-dropdown-link>
                    @endif
                    @endcan
                    @can('Import Manager Data Delete')
                    @if ($item->status !== ImportStatus::Pending && $item->status !== ImportStatus::Processing)
                    <div class="dropdown-divider"></div>
                    <button type="button" class="dropdown-item text-danger swal-delete"
                        data-url="{{ route('admin.download.import.manager.delete', ['downloadImportManager' => $item->id]) }}"
                        data-text="{{ __('importdownloadmanager::importdownloadmanager.index.delete_confirm') }}">
                        <i class="ph-trash"></i> {{ __('importdownloadmanager::importdownloadmanager.index.delete') }}
                    </button>
                    @endif
                    @endcan
                </x-dropdown-menu>
            </td>
            @endcanany
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>
@endsection



@push('scripts')
<script>
    $(document).ready(function() {
        setInterval(() => {
            getUpdateStatus();
        }, 5000);
    });

    function getUpdateStatus() {
        var statusClass = {
            'pending': 'badge bg-info-subtle text-info border border-info-subtle',
            'processing': 'badge bg-warning-subtle text-warning border border-warning-subtle',
            'failed': 'badge bg-danger-subtle text-danger border border-danger-subtle',
            'completed': 'badge bg-success-subtle text-success border border-success-subtle'
        };

        var itemIds = $('input[name="ids[]"]').map(function() {
            return $(this).val();
        }).get();

        if (itemIds.length > 0) {
            $.ajax({
                url: "{{ route('admin.download.import.status.update') }}",
                data: {
                    ids: itemIds
                },
                success: function(payload) {
                    const results = Array.isArray(payload.data) ? payload.data : payload;
                    if (results.length > 0) {
                        results.forEach(function(item) {
                            let id = item.id;
                            $('#remarks' + id).html(item.remarks);
                            let status = '#status' + id;
                            $(status).removeClass();
                            $(status).addClass(statusClass[item.status]);
                            $(status).text(item.status);
                        });
                    }
                }
            });
        }
        return false;
    }
</script>
@endpush