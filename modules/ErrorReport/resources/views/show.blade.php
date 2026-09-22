@extends('errorreport::layouts.master')
@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('errorreport::errorreport.show.breadcrumb', ['id' => $errorReport->id]) }}</span>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <span class="fd-icon-tile fd-icon-tile-sm {{ $errorReport->isResolved() ? 'is-success' : 'is-danger' }}"><i class="ph-bug"></i></span>
        <h5 class="card-title">{{ __('errorreport::errorreport.show.title') }}</h5>
        <div class="d-flex gap-2 ms-auto">
            @can('Resolve Error Report')
            @if (! $errorReport->isResolved())
            <form action="{{ route('admin.error-reports.resolve', $errorReport) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ph-check-circle"></i>{{ __('errorreport::errorreport.index.mark_resolved') }}
                </button>
            </form>
            @endif
            @endcan
            @can('Delete Error Report')
            <button type="button" class="btn btn-danger btn-sm swal-delete"
                data-url="{{ route('admin.error-reports.destroy', $errorReport) }}"
                data-text="{{ __('errorreport::errorreport.index.delete_confirm') }}">
                <i class="ph-trash"></i>{{ __('errorreport::errorreport.index.delete') }}
            </button>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <dl class="fd-dl">
            <dt>{{ __('errorreport::errorreport.show.exception_label') }}</dt>
            <dd><code>{{ $errorReport->exception_class }}</code></dd>

            <dt>{{ __('errorreport::errorreport.show.status_label') }}</dt>
            <dd>
                @if ($errorReport->isResolved())
                    <span class="fd-status is-success">{{ __('errorreport::errorreport.index.status_resolved') }}</span>
                @else
                    <span class="fd-status is-danger">{{ __('errorreport::errorreport.index.open') }}</span>
                @endif
            </dd>

            <dt>{{ __('errorreport::errorreport.show.message_label') }}</dt>
            <dd>{{ $errorReport->message }}</dd>

            <dt>{{ __('errorreport::errorreport.show.location_label') }}</dt>
            <dd><code>{{ $errorReport->file }}:{{ $errorReport->line }}</code></dd>

            <dt>{{ __('errorreport::errorreport.show.occurrences_label') }}</dt>
            <dd>{{ $errorReport->occurrences }}</dd>

            <dt>{{ __('errorreport::errorreport.show.first_seen_label') }}</dt>
            <dd>{{ $errorReport->first_seen_at->format(config('foundation.formats.datetime')) }}</dd>

            <dt>{{ __('errorreport::errorreport.show.last_seen_label') }}</dt>
            <dd>{{ $errorReport->last_seen_at->format(config('foundation.formats.datetime')) }}</dd>

            @if ($errorReport->request_url)
            <dt>{{ __('errorreport::errorreport.show.request_url_label') }}</dt>
            <dd class="text-break"><a href="{{ $errorReport->request_url }}" target="_blank" rel="noopener">{{ $errorReport->request_url }}</a></dd>
            @endif

            @if ($errorReport->request_method)
            <dt>{{ __('errorreport::errorreport.show.request_label') }}</dt>
            <dd>{{ $errorReport->request_method }} {{ $errorReport->request_path }}</dd>
            @endif
        </dl>

        @if ($errorReport->trace && count($errorReport->trace) > 0)
        <div class="fd-overline mt-4 mb-1">{{ __('errorreport::errorreport.show.stack_trace_label') }}</div>
        <pre class="bg-body-tertiary border rounded p-3 mb-0 fs-xs fd-scroll-y overflow-auto">@foreach ($errorReport->trace as $frame)
{{ ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '') }}()
    {{ ($frame['file'] ?? '') }}:{{ $frame['line'] ?? '' }}
@endforeach</pre>
        @endif
    </div>
</div>
@endsection
