@props([
'title' => '',
'data' => null,
'emptyMessage' => 'No data available',
'emptyIcon' => 'ph-tray',
])

@php
// Support both Paginator (has total()) and plain Collection/array (use count())
$isPaginator = isset($data) && method_exists($data, 'total');
$hasRows = isset($data) && ($isPaginator ? $data->total() > 0 : count($data) > 0);
$totalCount = isset($data) ? ($isPaginator ? $data->total() : count($data)) : 0;
@endphp

<div class="card">
    {{-- Header --}}
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 {{ isset($tabs) ? 'py-0' : 'py-2' }}">
        <div class="d-flex align-items-center gap-2 py-2">
            <h6 class="card-title mb-0 fw-semibold">{{ $title }}</h6>
            @if($hasRows)
            <span class="badge bg-secondary fw-normal">{{ number_format($totalCount) }}</span>
            @endif
        </div>

        @isset($tabs)
            <ul class="nav nav-tabs nav-tabs-highlight card-header-tabs border-bottom-0 mb-0">
                {{ $tabs }}
            </ul>
        @endisset

        <div class="d-flex align-items-center gap-2 flex-wrap py-2">
            @isset($actions)
            {{ $actions }}
            @endisset
            @isset($exports)
                {{ $exports }}
            @endisset

        </div>
    </div>

    {{-- Body --}}
    <div class="card-body p-0">
        @if($hasRows)
        <div class="table-responsive" style="min-height: 375px;">
            <table class="table table-hover table-borderless table-xs align-middle mb-0">
                {{ $slot }}
            </table>
        </div>

        {{-- Footer: only shown for paginated data --}}
        @if($isPaginator)
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top flex-wrap gap-2">
            <div class="flex-1">
                <span class="text-muted fs-sm">
                    Showing {{ $data->firstItem() }}–{{ $data->lastItem() }} of {{ number_format($data->total()) }}
                </span>
            </div>

            <div class="d-flex justify-content-center flex-1">
                {{ $data->withQueryString()->links() }}
            </div>

            <div class="d-flex align-items-center justify-content-end flex-1">
                {{-- Navigation control, not a form field: no name and no <form> wrapper, so an
                     enclosing form (e.g. a bulk-action POST) can never capture or submit it. --}}
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted fs-sm mb-0 text-nowrap">Per page</label>
                    <select class="form-control form-control-sm select js-per-page">
                        @foreach(getParPagePaginate() as $size => $label)
                            <option value="{{ $size }}" @selected((int) $size === perPage())>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        @endif
        @else
        <div class="text-center py-5 text-muted d-flex flex-column align-items-center justify-content-center" style="min-height: 400px;">
            <i class="{{ $emptyIcon }} d-block mb-2 opacity-25 fs-1"></i>
            <p class="mb-0 fs-sm">{{ $emptyMessage }}</p>
        </div>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
$(function () {
    // Per page is navigation, not a form field: rewrite the URL instead of submitting a
    // form, so a table wrapped in a bulk-action form can never post by accident.
    $(document).on('change', '.js-per-page', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', this.value);
        url.searchParams.delete('page'); // a new page size starts at page 1
        window.location.assign(url.toString());
    });
});
</script>
@endpush
@endonce
