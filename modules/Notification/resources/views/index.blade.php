@extends('notification::layouts.master')

@php
    $typeOptions = [
        '' => __('notification::notification.index.type_all'),
        'info' => __('notification::notification.index.type_info'),
        'export' => __('notification::notification.index.type_export'),
        'error' => __('notification::notification.index.type_error'),
    ];
    $statusOptions = [
        '' => __('notification::notification.index.status_filter_all'),
        'true' => __('notification::notification.index.status_read'),
        'false' => __('notification::notification.index.status_unread'),
    ];
@endphp

@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('notification::notification.index.breadcrumb') }}</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-6 mb-2">
        <x-form.select class="select" name="type" label="{{ __('notification::notification.index.filter_type_label') }}" :options="$typeOptions" :selected="request('type')" data-placeholder="{{ __('notification::notification.index.type_all') }}" />
    </div>
    <div class="col-md-6 mb-2">
        <x-form.select class="select" name="read" label="{{ __('notification::notification.index.filter_status_label') }}" :options="$statusOptions" :selected="request('read')" data-placeholder="{{ __('notification::notification.index.status_filter_all') }}" />
    </div>
</x-search-card>

<x-table-view-pagination title="{{ __('notification::notification.index.breadcrumb') }}" :data="$notifications" empty-icon="ph-bell-slash" empty-message="{{ __('notification::notification.index.empty') }}">
    <x-slot name="actions">
        <x-table-actions>
            <form action="{{ route('admin.notification.mark-all-as-read') }}" method="POST" class="d-inline">
                @csrf @method('PATCH')
                <x-table-action icon="ph-checks" title="{{ __('notification::notification.index.mark_all_as_read') }}" class="swal-confirm" data-text="{{ __('notification::notification.index.mark_all_as_read_confirm') }}" />
            </form>
        </x-table-actions>
    </x-slot>

    <thead>
        <tr>
            <th>#</th>
            <th class="text-nowrap">{{ __('notification::notification.index.col_type') }}</th>
            <th>{{ __('notification::notification.index.col_message') }}</th>
            <th class="text-nowrap">{{ __('foundation::foundation.common.status') }}</th>
            <th class="text-nowrap">{{ __('notification::notification.index.col_date') }}</th>
            <th class="text-end text-nowrap">{{ __('foundation::foundation.common.action') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($notifications as $notification)
        <tr class="{{ $notification->unread() ? 'table-active' : '' }}">
            <td>{{ $loop->iteration }}</td>
            <td>
                @php
                    $typeConfig = match($notification->type) {
                        'export' => ['bg' => 'success', 'icon' => 'ph-download-simple'],
                        'error' => ['bg' => 'danger', 'icon' => 'ph-x-circle'],
                        default => ['bg' => 'primary', 'icon' => 'ph-info'],
                    };
                @endphp
                <span class="badge bg-{{ $typeConfig['bg'] }}">
                    <i class="ph {{ $typeConfig['icon'] }}"></i>
                    {{ ucfirst($notification->type) }}
                </span>
            </td>
            <td>
                <div class="d-flex flex-column gap-1">
                    <span class="fw-medium text-strong">{{ $notification->title }}</span>
                    @if($notification->body)
                        <span class="text-muted fs-sm">{{ Str::limit($notification->body, 80) }}</span>
                    @endif
                    @if($notification->download_url)
                        <a href="{{ $notification->download_url }}" class="btn btn-sm btn-light align-self-start mt-1" target="_blank" rel="noopener">
                            <i class="ph-download-simple"></i>{{ __('notification::notification.index.download') }}
                        </a>
                    @endif
                </div>
            </td>
            <td>
                @if ($notification->read_at)
                <span class="fd-status is-success">{{ __('notification::notification.index.status_read') }}</span>
                @else
                <span class="fd-status is-warning">{{ __('notification::notification.index.status_unread') }}</span>
                @endif
            </td>
            <td class="text-nowrap fs-sm">{{ $notification->created_at->diffForHumans() }}</td>
            <td class="text-end">
                <x-dropdown-menu>
                    <x-dropdown-link :url="route('admin.notification.show', $notification->id)">
                        <i class="ph-eye"></i>{{ __('foundation::foundation.common.view') }}
                    </x-dropdown-link>
                    @if($notification->download_url)
                    <a href="{{ $notification->download_url }}" class="dropdown-item" target="_blank" rel="noopener">
                        <i class="ph-download-simple"></i>{{ __('notification::notification.index.download') }}
                    </a>
                    @endif
                    @if ($notification->read_at)
                    <form action="{{ route('admin.notification.mark-as-unread', $notification->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="dropdown-item">
                            <i class="ph-envelope-simple"></i>{{ __('notification::notification.index.mark_as_unread') }}
                        </button>
                    </form>
                    @else
                    <form action="{{ route('admin.notification.mark-as-read', $notification->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="dropdown-item">
                            <i class="ph-envelope-open"></i>{{ __('notification::notification.index.mark_as_read') }}
                        </button>
                    </form>
                    @endif
                    <div class="dropdown-divider"></div>
                    <button type="button" class="dropdown-item text-danger swal-delete"
                        data-url="{{ route('admin.notification.destroy', $notification->id) }}"
                        data-text="{{ __('notification::notification.index.delete_confirm') }}">
                        <i class="ph-trash"></i>{{ __('foundation::foundation.common.delete') }}
                    </button>
                </x-dropdown-menu>
            </td>
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>
@endsection