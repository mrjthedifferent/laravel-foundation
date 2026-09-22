@extends('notification::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.notification.index') }}" class="breadcrumb-item">{{ __('notification::notification.index.breadcrumb') }}</a>
    <span class="breadcrumb-item active">{{ __('notification::notification.show.breadcrumb') }}</span>
@endsection

@section('content')

    <x-page-header
        title="{{ __('notification::notification.show.title') }}"
        icon="ph-bell"
        :back-url="route('admin.notification.index')"
        back-label="{{ __('notification::notification.show.back_to_list') }}">
        <x-slot name="actions">
            @if ($notification->read_at)
                <form action="{{ route('admin.notification.mark-as-unread', $notification->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="ph-envelope-simple me-1"></i>{{ __('notification::notification.show.mark_as_unread') }}
                    </button>
                </form>
            @else
                <form action="{{ route('admin.notification.mark-as-read', $notification->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="ph-envelope-open me-1"></i>{{ __('notification::notification.show.mark_as_read') }}
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.notification.destroy', $notification->id) }}"
               class="btn btn-sm btn-outline-danger swal-delete"
               data-text="{{ __('notification::notification.show.delete_confirm') }}">
                <i class="ph-trash me-1"></i>{{ __('foundation::foundation.common.delete') }}
            </a>
        </x-slot>
    </x-page-header>

    <div class="row g-3">
        <div class="col-md-6">
            <x-form-section title="{{ __('notification::notification.show.meta_title') }}" icon="ph-info">
                <table class="table table-nowrap mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm" width="130">{{ __('notification::notification.show.col_id') }}</th>
                            <td class="fs-sm font-monospace">{{ $notification->id }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">{{ __('notification::notification.show.col_type') }}</th>
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
                            <th class="text-muted fw-semibold fs-sm">{{ __('foundation::foundation.common.status') }}</th>
                            <td>
                                @if ($notification->read_at)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('notification::notification.show.status_read') }}</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('notification::notification.show.status_unread') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">{{ __('notification::notification.show.col_created') }}</th>
                            <td class="fs-sm">
                                {{ $notification->created_at->format(config('foundation.formats.datetime')) }}
                                <div class="text-muted fs-xs">{{ $notification->created_at->diffForHumans() }}</div>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-semibold fs-sm">{{ __('notification::notification.show.col_read_at') }}</th>
                            <td class="fs-sm">
                                @if($notification->read_at)
                                    {{ $notification->read_at->format(config('foundation.formats.datetime')) }}
                                    <div class="text-muted fs-xs">{{ $notification->read_at->diffForHumans() }}</div>
                                @else
                                    <span class="text-muted">{{ __('notification::notification.show.not_read_yet') }}</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </x-form-section>
        </div>
        <div class="col-md-6">
            <x-form-section title="{{ __('notification::notification.show.message_title') }}" icon="ph-chat-text">
                <div class="mb-3">
                    <h5 class="fw-semibold mb-2">{{ $notification->title }}</h5>
                    @if($notification->body)
                        <p class="text-muted mb-0">{{ $notification->body }}</p>
                    @endif
                </div>
                @if($notification->download_url)
                    <a href="{{ $notification->download_url }}" class="btn btn-success" target="_blank" rel="noopener">
                        <i class="ph-download-simple me-2"></i>{{ __('notification::notification.show.download_file') }}
                    </a>
                @endif
                @if(count($notification->data_payload) > 0)
                    <details class="mt-3">
                        <summary class="form-label fw-semibold fs-xs text-muted text-uppercase cursor-pointer" style="letter-spacing:.04em;">{{ __('notification::notification.show.additional_data') }}</summary>
                        <pre class="border rounded p-3 bg-body-tertiary font-monospace fs-xs mt-2 mb-0" style="white-space:pre-wrap;word-break:break-all;">{{ json_encode($notification->data_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            </x-form-section>
        </div>
    </div>

@endsection
