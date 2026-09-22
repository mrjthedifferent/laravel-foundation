<x-app-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">{{ __('foundation::foundation.layout.home') }}</a>
        <span class="breadcrumb-item active">{{ __('foundation::foundation.dashboard.breadcrumb') }}</span>
    </x-slot>

    @php
        $greeting = match (true) {
            now()->hour < 12 => __('foundation::foundation.dashboard.greeting_morning'),
            now()->hour < 18 => __('foundation::foundation.dashboard.greeting_afternoon'),
            default => __('foundation::foundation.dashboard.greeting_evening'),
        };

        // Windows are a fixed list, not a range: an arbitrary ?days would mean an
        // arbitrary number of buckets and SVG points.
        $windows = [14, 30];
        $days = in_array(request()->integer('days'), $windows, true) ? request()->integer('days') : $windows[0];

        $stats = app(\Mrj\Foundation\Services\Dashboard\StatRegistry::class)->all();
        $chart = app(\Mrj\Foundation\Services\Dashboard\ChartRegistry::class)->first($days);
        // Rendered up front: the feed's composer decides whether this viewer sees
        // anything at all, and an empty card would leave the chart in a narrow column.
        $feedView = 'activitylog::partials.dashboard-feed';
        $feed = view()->exists($feedView) ? trim(view($feedView)->render()) : '';
        $hasFeed = $feed !== '';
    @endphp

    {{-- A project replaces this page by creating resources/views/dashboard.blade.php. --}}

    <x-page-header :title="$greeting.', '.Auth::user()->name" :subtitle="now()->translatedFormat('l, j F Y')" />

    {{-- Headline stats: every enabled module contributes its own. --}}
    @if ($stats)
        <div class="row g-3 mb-4">
            @foreach ($stats as $stat)
                <div class="col-sm-6 col-xl-3">
                    <x-stat-card
                        :label="$stat['label']"
                        :value="$stat['value']"
                        :icon="$stat['icon'] ?? 'ph-chart-bar'"
                        :color="$stat['color'] ?? 'primary'"
                        :href="$stat['href'] ?? null"
                        :change="$stat['change'] ?? null"
                        :change-up="$stat['changeUp'] ?? true"
                        :caption="$stat['caption'] ?? null" />
                </div>
            @endforeach
        </div>
    @endif

    @if ($chart || $hasFeed)
        <div class="row g-3 mb-4">
            @if ($chart)
                <div class="{{ $hasFeed ? 'col-xl-8' : 'col-12' }}">
                    <div class="card h-100">
                        <div class="card-header">
                            <h2 class="card-title">{{ $chart['label'] }}</h2>
                            <div class="ms-auto nav nav-pills">
                                @foreach ($windows as $window)
                                    <a class="nav-link @if ($days === $window) active @endif"
                                        @if ($days === $window) aria-current="page" @endif
                                        href="{{ route('admin.dashboard', ['days' => $window]) }}">
                                        {{ __('foundation::foundation.dashboard.last_days', ['days' => $window]) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="card-body">
                            <x-chart-area :series="$chart['series']" :label="$chart['label']" />
                        </div>
                    </div>
                </div>
            @endif

            @if ($hasFeed)
                <div class="{{ $chart ? 'col-xl-4' : 'col-12' }}">
                    {!! $feed !!}
                </div>
            @endif
        </div>
    @endif

    {{-- Every enabled module contributes its own widget. --}}
    <div class="row g-3">
        @foreach (\Nwidart\Modules\Facades\Module::allEnabled() as $module)
            @php $widgetView = strtolower($module->getName()) . '::partials.dashboard-widget'; @endphp
            @if (view()->exists($widgetView))
                @include($widgetView)
            @endif
        @endforeach
    </div>
</x-app-layout>
