@extends('notification::layouts.master')

@php
    $typeOptions = ['' => 'All Types', 'info' => 'Info', 'export' => 'Export', 'error' => 'Error'];
@endphp

@section('breadcrumb')
<span class="breadcrumb-item active">Notifications</span>
@endsection

@section('content')
<x-search-card>
    <div class="col-md-6 mb-2">
        <x-form.select class="select" name="type" label="Type" :options="$typeOptions" :selected="request('type')" data-placeholder="All Types" />
    </div>
    <div class="col-md-6 mb-2">
        <x-form.select class="select" name="read" label="Status" :options="['' => 'All', 'true' => 'Read', 'false' => 'Unread']" :selected="request('read')" data-placeholder="All" />
    </div>
</x-search-card>

<x-table-view-pagination title="Notifications" :data="$notifications" empty-icon="ph-bell-slash" empty-message="No notifications found">
    <x-slot name="actions">
        <x-table-actions>
            <form action="{{ route('admin.notification.mark-all-as-read') }}" method="POST" class="d-inline">
                @csrf @method('PATCH')
                <x-table-action icon="ph-checks" title="Mark All as Read" class="swal-confirm" data-text="Mark all notifications as read?" />
            </form>
        </x-table-actions>
    </x-slot>

    <thead>
        <tr>
            <th style="width:44px">#</th>
            <th style="width:100px">Type</th>
            <th>Message</th>
            <th style="width:90px">Status</th>
            <th style="width:130px">Date</th>
            <th class="text-end" style="width:100px">Action</th>
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
                <span class="badge bg-{{ $typeConfig['bg'] }}-subtle text-{{ $typeConfig['bg'] }} border border-{{ $typeConfig['bg'] }}-subtle fs-xs d-inline-flex align-items-center gap-1">
                    <i class="ph {{ $typeConfig['icon'] }}"></i>
                    {{ ucfirst($notification->type) }}
                </span>
            </td>
            <td>
                <div class="d-flex flex-column gap-1">
                    <strong class="text-body">{{ $notification->title }}</strong>
                    @if($notification->body)
                        <span class="text-muted fs-sm">{{ Str::limit($notification->body, 80) }}</span>
                    @endif
                    @if($notification->download_url)
                        <a href="{{ $notification->download_url }}" class="btn btn-sm btn-success align-self-start mt-1" target="_blank" rel="noopener">
                            <i class="ph-download-simple me-1"></i>Download
                        </a>
                    @endif
                </div>
            </td>
            <td>
                @if ($notification->read_at)
                <span class="badge bg-success-subtle text-success border border-success-subtle">Read</span>
                @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Unread</span>
                @endif
            </td>
            <td class="text-nowrap fs-sm">{{ $notification->created_at->diffForHumans() }}</td>
            <td class="text-end">
                <x-dropdown-menu>
                    <x-dropdown-link :url="route('admin.notification.show', $notification->id)">
                        <i class="ph-eye me-2"></i>View
                    </x-dropdown-link>
                    @if($notification->download_url)
                    <a href="{{ $notification->download_url }}" class="dropdown-item" target="_blank" rel="noopener">
                        <i class="ph-download-simple me-2"></i>Download
                    </a>
                    @endif
                    @if ($notification->read_at)
                    <form action="{{ route('admin.notification.mark-as-unread', $notification->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="dropdown-item">
                            <i class="ph-envelope-simple me-2"></i>Mark as Unread
                        </button>
                    </form>
                    @else
                    <form action="{{ route('admin.notification.mark-as-read', $notification->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="dropdown-item">
                            <i class="ph-envelope-open me-2"></i>Mark as Read
                        </button>
                    </form>
                    @endif
                    <div class="dropdown-divider"></div>
                    <button type="button" class="dropdown-item text-danger swal-delete"
                        data-url="{{ route('admin.notification.destroy', $notification->id) }}"
                        data-text="Delete this notification?">
                        <i class="ph-trash me-2"></i>Delete
                    </button>
                </x-dropdown-menu>
            </td>
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>
@endsection