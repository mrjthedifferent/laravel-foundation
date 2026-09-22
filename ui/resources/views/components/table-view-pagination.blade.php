@props([
'title' => '',
'data' => null,
'emptyMessage' => null,
'emptyIcon' => 'ph-tray',
])

@php
    $emptyMessage = $emptyMessage ?? __('foundation::foundation.table.empty_default');
@endphp

@php
// Support both Paginator (has total()) and plain Collection/array (use count())
$isPaginator = isset($data) && method_exists($data, 'total');
$hasRows = isset($data) && ($isPaginator ? $data->total() > 0 : count($data) > 0);
$totalCount = isset($data) ? ($isPaginator ? $data->total() : count($data)) : 0;
@endphp

<div class="card">
    {{-- Header --}}
    <div class="card-header {{ isset($tabs) ? 'pb-0' : '' }}">
        <h6 class="card-title">{{ $title }}</h6>
        @if($hasRows)
        <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal">{{ number_format($totalCount) }}</span>
        @endif

        @isset($tabs)
            <ul class="nav nav-tabs card-header-tabs mb-0">
                {{ $tabs }}
            </ul>
        @endisset

        <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
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
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                {{ $slot }}
            </table>
        </div>

        {{-- Footer: only shown for paginated data --}}
        @if($isPaginator)
        <div class="fd-table-foot">
            <span>
                {{ __('foundation::foundation.table.showing', ['first' => $data->firstItem(), 'last' => $data->lastItem(), 'total' => number_format($data->total())]) }}
            </span>

            <div class="d-flex align-items-center gap-3 flex-wrap ms-auto">
                {{-- Navigation control, not a form field: no name and no <form> wrapper, so an
                     enclosing form (e.g. a bulk-action POST) can never capture or submit it. --}}
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 text-nowrap">{{ __('foundation::foundation.table.per_page') }}</label>
                    <select class="form-select form-select-sm select js-per-page">
                        @foreach(getParPagePaginate() as $size => $label)
                            <option value="{{ $size }}" @selected((int) $size === perPage())>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{ $data->withQueryString()->links() }}
            </div>
        </div>
        @endif
        @else
        <div class="fd-empty">
            <span class="fd-empty-icon"><i class="{{ $emptyIcon }}"></i></span>
            <p class="fd-empty-title">{{ $emptyMessage }}</p>
            @isset($emptyAction)
                {{ $emptyAction }}
            @endisset
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
