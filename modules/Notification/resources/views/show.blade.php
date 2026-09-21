@extends('notification::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.notification.index') }}" class="breadcrumb-item">Notifications</a>
    <span class="breadcrumb-item active">View Notification</span>
@endsection

@section('content')

    <x-page-header
        title="Notification Details"
        icon="ph-bell"
        :back-url="route('admin.notification.index')"
        back-label="Back to List">
        <x-slot name="actions">
            @if ($notification->read_at)
                <form action="{{ route('admin.notification.mark-as-unread', $notification->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="ph-envelope-simple me-1"></i>Mark as Unread
                    </button>
                </form>
            @else
                <form action="{{ route('admin.notification.mark-as-read', $notification->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="ph-envelope-open me-1"></i>Mark as Read
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.notification.destroy', $notification->id) }}"
               class="btn btn-sm btn-outline-danger swal-delete"
               data-text="Delete this notification?">
                <i class="ph-trash me-1"></i>Delete
            </a>
        </x-slot>
    </x-page-header>

    <div class="row g-3">
        <div class="col-md-6">
            <x-form-section title="Meta" icon="ph-info">
                <table class="table table-nowrap mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm" width="130">ID</th>
                            <td class="fs-sm font-monospace">{{ $notification->id }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">Type</th>
                            <td>
                                @php
                                    $typeConfig = match($notification->type) {
                                        'export' => ['bg' => 'success', 'icon' => 'ph-download-simple'],
                                        'error' => ['bg' => 'danger', 'icon' => 'ph-x-circle'],
                                        default => ['bg' => 'primary', 'icon' => 'ph-info'],
                                    };
                                @endphp
                                <span class="badge bg-{{ $typeConfig['bg'] }}-subtle text-{{ $typeConfig['bg'] }} border border-{{ $typeConfig['bg'] }}-subtle fs-xs">
                                    <i class="ph {{ $typeConfig['icon'] }} me-1"></i>{{ ucfirst($notification->type) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">Status</th>
                            <td>
                                @if ($notification->read_at)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Read</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Unread</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">Created</th>
                            <td class="fs-sm">
                                {{ $notification->created_at->format('Y-m-d H:i:s') }}
                                <div class="text-muted fs-xs">{{ $notification->created_at->diffForHumans() }}</div>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">Read At</th>
                            <td class="fs-sm">
                                @if($notification->read_at)
                                    {{ $notification->read_at->format('Y-m-d H:i:s') }}
                                    <div class="text-muted fs-xs">{{ $notification->read_at->diffForHumans() }}</div>
                                @else
                                    <span class="text-muted">Not read yet</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </x-form-section>
        </div>
        <div class="col-md-6">
            <x-form-section title="Message" icon="ph-chat-text">
                <div class="mb-3">
                    <h5 class="fw-semibold mb-2">{{ $notification->title }}</h5>
                    @if($notification->body)
                        <p class="text-muted mb-0">{{ $notification->body }}</p>
                    @endif
                </div>
                @if($notification->download_url)
                    <a href="{{ $notification->download_url }}" class="btn btn-success" target="_blank" rel="noopener">
                        <i class="ph-download-simple me-2"></i>Download File
                    </a>
                @endif
                @if(count($notification->data_payload) > 0)
                    <details class="mt-3">
                        <summary class="form-label fw-semibold fs-xs text-muted text-uppercase cursor-pointer" style="letter-spacing:.04em;">Additional Data</summary>
                        <pre class="border rounded p-3 bg-body-tertiary font-monospace fs-xs mt-2 mb-0" style="white-space:pre-wrap;word-break:break-all;">{{ json_encode($notification->data_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            </x-form-section>
        </div>
    </div>

@endsection
