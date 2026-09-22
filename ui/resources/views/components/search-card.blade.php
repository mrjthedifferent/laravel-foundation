@props(['resetRoute' => null])

<div class="card mb-3">
    <div class="card-body pb-1">
        <form method="GET" action="{{ url()->current() }}">
        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        @isset($wide)
            {{-- Full-width filters (e.g. the org-unit cascade) get their own row above the
                 compact filters + buttons, so they never break that row's alignment. --}}
            <div class="row g-2 mb-2">
                {{ $wide }}
            </div>
        @endisset
        <div class="row g-2 align-items-end">
            {{ $slot }}
            <div class="col-md-auto ms-auto mb-2 d-flex gap-2 align-items-end">
                <a href="{{ $resetRoute ?? (function() { try { return route(Route::currentRouteName(), Route::current()?->parameters() ?? []); } catch (\Throwable) { return url()->current(); } })() }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="ph-arrow-counter-clockwise me-1"></i>Reset
                </a>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="ph-funnel me-1"></i>Filter
                </button>
            </div>
        </div>
        </form>
    </div>
</div>
