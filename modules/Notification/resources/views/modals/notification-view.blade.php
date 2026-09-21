<div class="modal-header">
    <h5 class="modal-title">Notification Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <h6 class="fw-bold fs-xs text-muted text-uppercase mb-1">Type</h6>
            @php
                $typeConfig = match($notification->type) {
                    'export' => ['bg' => 'success', 'icon' => 'ph-download-simple'],
                    'error' => ['bg' => 'danger', 'icon' => 'ph-x-circle'],
                    default => ['bg' => 'primary', 'icon' => 'ph-info'],
                };
            @endphp
            <span class="badge bg-{{ $typeConfig['bg'] }}-subtle text-{{ $typeConfig['bg'] }} border border-{{ $typeConfig['bg'] }}-subtle">
                <i class="ph {{ $typeConfig['icon'] }} me-1"></i>{{ ucfirst($notification->type) }}
            </span>
        </div>
        <div class="col-md-6">
            <h6 class="fw-bold fs-xs text-muted text-uppercase mb-1">Status</h6>
            @if ($notification->read_at)
                <span class="badge bg-success-subtle text-success border border-success-subtle">Read</span>
            @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Unread</span>
            @endif
        </div>
    </div>

    <div class="mb-3">
        <h6 class="fw-bold fs-xs text-muted text-uppercase mb-1">Date</h6>
        <p class="mb-0">{{ $notification->created_at->format(config('foundation.formats.datetime')) }} <span class="text-muted">({{ $notification->created_at->diffForHumans() }})</span></p>
    </div>

    <div class="mb-3">
        <h6 class="fw-bold fs-xs text-muted text-uppercase mb-1">Message</h6>
        <h5 class="fw-semibold mb-1">{{ $notification->title }}</h5>
        @if($notification->body)
            <p class="text-muted mb-2">{{ $notification->body }}</p>
        @endif
        @if($notification->download_url)
            <a href="{{ $notification->download_url }}" class="btn btn-success btn-sm" target="_blank" rel="noopener">
                <i class="ph-download-simple me-1"></i>Download File
            </a>
        @endif
    </div>

    @if(count($notification->data_payload) > 0)
        <details>
            <summary class="fw-bold fs-xs text-muted text-uppercase cursor-pointer">Additional Data</summary>
            <pre class="border rounded p-3 bg-body-tertiary font-monospace fs-xs mt-2 mb-0" style="max-height: 200px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;">{{ json_encode($notification->data_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </details>
    @endif
</div>
<div class="modal-footer">
    <div class="d-flex w-100 justify-content-between">
        <div>
            @if ($notification->read_at)
                <form action="{{ route('admin.notification.mark-as-unread', $notification->id) }}" method="POST"
                    class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="ph-envelope-simple me-1"></i> Mark as Unread
                    </button>
                </form>
            @else
                <form action="{{ route('admin.notification.mark-as-read', $notification->id) }}" method="POST"
                    class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ph-envelope-open me-1"></i> Mark as Read
                    </button>
                </form>
            @endif
        </div>
        <div>
            <a href="{{ route('admin.notification.destroy', $notification->id) }}"
               class="btn btn-danger btn-sm swal-delete"
               data-text="Are you sure you want to delete this notification?">
                <i class="ph-trash me-1"></i> Delete
            </a>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>

