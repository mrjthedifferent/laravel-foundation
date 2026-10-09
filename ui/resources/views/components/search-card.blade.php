@props(['resetRoute' => null])

{{-- The filter bar above a list: the search box, a Filters button that opens the rest of the fields,
     the active filters as removable chips, and Reset / Apply. The fields are whatever the page puts
     in the slot; foundation.js lifts the `search` input into the bar and builds the chips from the
     others. Without JavaScript the fields are simply all shown. --}}
@php
    $panelId = 'filters-'.substr(md5(url()->current()), 0, 8);
    $reset = $resetRoute ?? (function () {
        try {
            return route(Route::currentRouteName(), Route::current()?->parameters() ?? []);
        } catch (\Throwable) {
            return url()->current();
        }
    })();
@endphp

<div class="card fd-filterbar mb-4" data-fd-filterbar>
    <form method="GET" action="{{ url()->current() }}">
        <input type="hidden" name="per_page" value="{{ request('per_page') }}">

        <div class="fd-filterbar-row">
            <div class="fd-filterbar-search" data-fd-filter-search hidden>
                <i class="ph-magnifying-glass" aria-hidden="true"></i>
            </div>

            <button type="button" class="btn btn-light fd-filterbar-toggle" data-fd-filter-toggle
                aria-expanded="true" aria-controls="{{ $panelId }}">
                <i class="ph-funnel"></i>{{ __('foundation::foundation.common.filters') }}
                <span class="badge badge-count" data-fd-filter-count hidden></span>
            </button>

            <div class="fd-filterbar-actions">
                <a href="{{ $reset }}" class="btn btn-ghost">
                    <i class="ph-arrow-counter-clockwise"></i>{{ __('foundation::foundation.common.reset') }}
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="ph-check"></i>{{ __('foundation::foundation.common.filter') }}
                </button>
            </div>
        </div>

        <div class="fd-filterbar-chips" data-fd-filter-chips hidden></div>

        <div class="fd-filterbar-panel" id="{{ $panelId }}" data-fd-filter-panel>
            @isset($wide)
                {{-- Full-width filters (e.g. the org-unit cascade) get their own row above the
                     compact ones, so they never break that row's alignment. --}}
                <div class="grid grid-cols-12 gap-2 mb-2">
                    {{ $wide }}
                </div>
            @endisset
            <div class="grid grid-cols-12 gap-2 items-end">
                {{ $slot }}
            </div>
        </div>
    </form>
</div>
