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

        // The widgets come ready-rendered, in the viewer's own order and widths.
        // A project replaces this page by creating resources/views/dashboard.blade.php.
        ['context' => $context, 'items' => $items] = app(\Mrj\Foundation\Services\Dashboard\DashboardPage::class)->build(request());
        $windows = \Mrj\Foundation\Services\Dashboard\DashboardContext::WINDOWS;
    @endphp

    <x-page-header :title="$greeting.', '.Auth::user()->name" :subtitle="now()->translatedFormat('l, j F Y')">
        <x-slot name="actions">
            <nav class="fd-segmented" aria-label="{{ __('foundation::foundation.dashboard.range') }}">
                @foreach ($windows as $window)
                    <a href="{{ route('admin.dashboard', $context->query(['range' => $window])) }}"
                        class="@if ($context->days === $window) is-active @endif"
                        @if ($context->days === $window) aria-current="page"@endif>{{ __('foundation::foundation.dashboard.range_days', ['days' => $window]) }}</a>
                @endforeach
            </nav>
            <a href="{{ route('admin.dashboard', $context->query(['compare' => $context->compare ? null : 1])) }}"
                class="btn btn-light @if ($context->compare) is-on @endif" role="switch" aria-checked="{{ $context->compare ? 'true' : 'false' }}">
                <i class="ph ph-arrows-left-right"></i>{{ __('foundation::foundation.dashboard.compare') }}
            </a>
            <span class="fd-edit-controls">
                <button type="button" class="btn btn-light" data-fd-dash="edit">
                    <i class="ph ph-sliders-horizontal"></i>{{ __('foundation::foundation.dashboard.customize') }}
                </button>
                <button type="button" class="btn btn-ghost fd-edit-only" data-fd-dash="reset">
                    <i class="ph ph-arrow-counter-clockwise"></i>{{ __('foundation::foundation.dashboard.reset_layout') }}
                </button>
                <button type="button" class="btn btn-primary fd-edit-only" data-fd-dash="done">
                    <i class="ph ph-check"></i>{{ __('foundation::foundation.dashboard.done') }}
                </button>
            </span>
        </x-slot>
    </x-page-header>

    <div class="fd-dashboard" data-fd-dashboard
        data-url-save="{{ route('admin.dashboard.layout.update') }}"
        data-url-reset="{{ route('admin.dashboard.layout.reset') }}"
        data-msg-error="{{ __('foundation::foundation.dashboard.layout_error') }}"
        data-msg-reset-title="{{ __('foundation::foundation.dashboard.reset_title') }}"
        data-msg-reset-text="{{ __('foundation::foundation.dashboard.reset_text') }}">
        <p class="fd-edit-hint fd-edit-only" role="status">{{ __('foundation::foundation.dashboard.edit_hint') }}</p>

        @foreach ($items as $item)
            <section class="fd-widget fd-w-{{ $item['width'] }} @if ($item['hidden']) is-hidden @endif"
                data-fd-widget data-key="{{ $item['key'] }}" data-width="{{ $item['width'] }}"
                data-hidden="{{ $item['hidden'] ? 'true' : 'false' }}" aria-label="{{ $item['title'] }}">
                <div class="fd-widget-bar fd-edit-only">
                    <span class="fd-widget-handle" draggable="true" title="{{ __('foundation::foundation.dashboard.drag') }}"><i class="ph ph-dots-six-vertical"></i></span>
                    <span class="fd-widget-name"><i class="{{ $item['icon'] }}"></i>{{ $item['title'] }}</span>
                    <span class="fd-widget-tools">
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" data-fd-widget-act="earlier" aria-label="{{ __('foundation::foundation.dashboard.move_earlier') }}"><i class="ph ph-arrow-up"></i></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" data-fd-widget-act="later" aria-label="{{ __('foundation::foundation.dashboard.move_later') }}"><i class="ph ph-arrow-down"></i></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" data-fd-widget-act="narrower" aria-label="{{ __('foundation::foundation.dashboard.narrower') }}"><i class="ph ph-arrows-in-line-horizontal"></i></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" data-fd-widget-act="wider" aria-label="{{ __('foundation::foundation.dashboard.wider') }}"><i class="ph ph-arrows-out-line-horizontal"></i></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" data-fd-widget-act="toggle" aria-label="{{ __('foundation::foundation.dashboard.show_hide') }}"><i class="{{ $item['hidden'] ? 'ph ph-eye-slash' : 'ph ph-eye' }}"></i></button>
                    </span>
                </div>
                <div class="fd-widget-body">
                    @if ($item['hidden'])
                        <p class="fd-widget-hidden-note"><i class="ph ph-eye-slash"></i>{{ __('foundation::foundation.dashboard.hidden_note') }}</p>
                    @else
                        {!! $item['html'] !!}
                    @endif
                </div>
            </section>
        @endforeach

        @if ($items === [])
            <div class="fd-widget fd-w-12">
                <div class="card"><div class="fd-empty">
                    <span class="fd-empty-icon"><i class="ph ph-squares-four"></i></span>
                    <p class="fd-empty-title">{{ __('foundation::foundation.dashboard.nothing_to_show') }}</p>
                </div></div>
            </div>
        @endif
    </div>
</x-app-layout>
