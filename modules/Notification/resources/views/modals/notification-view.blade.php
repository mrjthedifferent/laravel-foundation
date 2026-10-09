<div class="modal-header">
    <h5 class="modal-title">{{ __('notification::notification.modal.title') }}</h5>
    <button type="button" class="btn-close" data-fd-dismiss="modal" aria-label="{{ __('foundation::foundation.common.close') }}"></button>
</div>
<div class="modal-body">
    @php
        $typeConfig = match($notification->type) {
            'export' => ['bg' => 'success', 'icon' => 'ph ph-download-simple'],
            'error' => ['bg' => 'danger', 'icon' => 'ph ph-x-circle'],
            default => ['bg' => 'primary', 'icon' => 'ph ph-info'],
        };
    @endphp

    <dl class="fd-dl mb-4">
        <dt>{{ __('notification::notification.modal.type_label') }}</dt>
        <dd>
            <span class="badge badge-{{ $typeConfig['bg'] }}">
                <i class="ph {{ $typeConfig['icon'] }}"></i>{{ ucfirst($notification->type) }}
            </span>
        </dd>

        <dt>{{ __('foundation::foundation.common.status') }}</dt>
        <dd>
            @if ($notification->read_at)
                <span class="fd-status is-success">{{ __('notification::notification.modal.status_read') }}</span>
            @else
                <span class="fd-status is-warning">{{ __('notification::notification.modal.status_unread') }}</span>
            @endif
        </dd>

        <dt>{{ __('notification::notification.modal.date_label') }}</dt>
        <dd>
            {{ $notification->created_at->format(config('foundation.formats.datetime')) }}
            <span class="text-muted">({{ $notification->created_at->diffForHumans() }})</span>
        </dd>
    </dl>

    <div class="mb-4">
        <div class="fd-overline mb-1">{{ __('notification::notification.modal.message_label') }}</div>
        <h5 class="font-semibold mb-1">{{ $notification->title }}</h5>
        @if($notification->body)
            <p class="text-muted mb-2">{{ $notification->body }}</p>
        @endif
        @if($notification->download_url)
            <a href="{{ $notification->download_url }}" class="btn btn-light btn-sm" target="_blank" rel="noopener">
                <i class="ph ph-download-simple"></i>{{ __('notification::notification.modal.download_file') }}
            </a>
        @endif
    </div>

    @if(count($notification->data_payload) > 0)
        <details>
            <summary class="fd-overline cursor-pointer">{{ __('notification::notification.modal.additional_data') }}</summary>
            <pre class="border rounded-md p-4 bg-subtle font-mono text-xs mt-2 mb-0 fd-scroll-y overflow-auto">{{ json_encode($notification->data_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </details>
    @endif
</div>
<div class="modal-footer">
    <div class="flex w-full justify-between">
        <div>
            @if ($notification->read_at)
                <form action="{{ route('admin.notification.mark-as-unread', $notification->id) }}" method="POST"
                    class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-light btn-sm">
                        <i class="ph ph-envelope-simple"></i>{{ __('notification::notification.modal.mark_as_unread') }}
                    </button>
                </form>
            @else
                <form action="{{ route('admin.notification.mark-as-read', $notification->id) }}" method="POST"
                    class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-light btn-sm">
                        <i class="ph ph-envelope-open"></i>{{ __('notification::notification.modal.mark_as_read') }}
                    </button>
                </form>
            @endif
        </div>
        <div>
            <a href="{{ route('admin.notification.destroy', $notification->id) }}"
               class="btn btn-outline-danger btn-sm swal-delete"
               data-text="{{ __('notification::notification.modal.delete_confirm') }}">
                <i class="ph ph-trash"></i>{{ __('foundation::foundation.common.delete') }}
            </a>
            <button type="button" class="btn btn-light btn-sm" data-fd-dismiss="modal">{{ __('foundation::foundation.common.close') }}</button>
        </div>
    </div>
</div>

