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
                    <button type="submit" class="btn btn-light">
                        <i class="ph-envelope-simple"></i>{{ __('notification::notification.show.mark_as_unread') }}
                    </button>
                </form>
            @else
                <form action="{{ route('admin.notification.mark-as-read', $notification->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-light">
                        <i class="ph-envelope-open"></i>{{ __('notification::notification.show.mark_as_read') }}
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.notification.destroy', $notification->id) }}"
               class="btn btn-outline-danger swal-delete"
               data-text="{{ __('notification::notification.show.delete_confirm') }}">
                <i class="ph-trash"></i>{{ __('foundation::foundation.common.delete') }}
            </a>
        </x-slot>
    </x-page-header>

    <div class="row g-3">
        <div class="col-md-6">
            <x-form-section title="{{ __('notification::notification.show.meta_title') }}" icon="ph-info">
                @php
                    $typeConfig = match($notification->type) {
                        'export' => ['bg' => 'success', 'icon' => 'ph-download-simple'],
                        'error' => ['bg' => 'danger', 'icon' => 'ph-x-circle'],
                        default => ['bg' => 'primary', 'icon' => 'ph-info'],
                    };
                @endphp
                <dl class="fd-dl">
                    <dt>{{ __('notification::notification.show.col_id') }}</dt>
                    <dd class="font-monospace fs-sm text-break">{{ $notification->id }}</dd>

                    <dt>{{ __('notification::notification.show.col_type') }}</dt>
                    <dd>
                        <span class="badge bg-{{ $typeConfig['bg'] }}">
                            <i class="ph {{ $typeConfig['icon'] }}"></i>{{ ucfirst($notification->type) }}
                        </span>
                    </dd>

                    <dt>{{ __('foundation::foundation.common.status') }}</dt>
                    <dd>
                        @if ($notification->read_at)
                            <span class="fd-status is-success">{{ __('notification::notification.show.status_read') }}</span>
                        @else
                            <span class="fd-status is-warning">{{ __('notification::notification.show.status_unread') }}</span>
                        @endif
                    </dd>

                    <dt>{{ __('notification::notification.show.col_created') }}</dt>
                    <dd>
                        {{ $notification->created_at->format(config('foundation.formats.datetime')) }}
                        <div class="text-muted fs-xs">{{ $notification->created_at->diffForHumans() }}</div>
                    </dd>

                    <dt>{{ __('notification::notification.show.col_read_at') }}</dt>
                    <dd>
                        @if($notification->read_at)
                            {{ $notification->read_at->format(config('foundation.formats.datetime')) }}
                            <div class="text-muted fs-xs">{{ $notification->read_at->diffForHumans() }}</div>
                        @else
                            <span class="text-muted">{{ __('notification::notification.show.not_read_yet') }}</span>
                        @endif
                    </dd>
                </dl>
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
                    <a href="{{ $notification->download_url }}" class="btn btn-light" target="_blank" rel="noopener">
                        <i class="ph-download-simple"></i>{{ __('notification::notification.show.download_file') }}
                    </a>
                @endif
                @if(count($notification->data_payload) > 0)
                    <details class="mt-3">
                        <summary class="fd-overline cursor-pointer">{{ __('notification::notification.show.additional_data') }}</summary>
                        <pre class="border rounded p-3 bg-body-tertiary font-monospace fs-xs mt-2 mb-0 overflow-auto">{{ json_encode($notification->data_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            </x-form-section>
        </div>
    </div>

@endsection
