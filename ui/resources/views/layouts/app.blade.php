<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $theme['direction'] }}"
    data-bs-theme="{{ $theme['colorMode'] === 'dark' ? 'dark' : 'light' }}" 
    data-color-palette="{{ $theme['palette'] }}" data-theme-layout="{{ $theme['layout'] }}"
    class="{{ $theme['isLayoutStatic'] ? 'layout-static' : '' }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.partials.head-scripts', ['theme' => $theme])

    <title>{{ config('settings.app_name.value') ?: config('app.name', 'App') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $theme['favicon'] }}">

    @include('layouts.partials.head-styles', ['theme' => $theme])

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
    @stack('head_scripts')
</head>

<body class="{{ $theme['isLayoutStatic'] ? 'layout-static' : '' }}">

    @include('layouts.partials.impersonation-banner')

    @if ($theme['layout'] === '2')
        <div class="page-content">
            @include('layouts.partials.sidebar', ['theme' => $theme])
            <div class="content-wrapper">
                @include('layouts.partials.navbar', ['theme' => $theme])
                <div class="content-inner">
                    @include('layouts.partials.page-header')
                    <div class="content pt-0">{{ $slot }}</div>
                    @include('layouts.partials.footer')
                </div>
            </div>
        </div>
    @elseif($theme['layout'] === '3')
        @include('layouts.partials.navbar', ['theme' => $theme])
        <div class="page-content pt-0">
            @include('layouts.partials.sidebar', ['theme' => $theme])
            <div class="content-wrapper">
                <div class="content-inner">
                    @include('layouts.partials.page-header')
                    <div class="content pt-0">{{ $slot }}</div>
                </div>
            </div>
        </div>
        @include('layouts.partials.footer')
    @else
        @include('layouts.partials.navbar', ['theme' => $theme])
        <div class="page-content">
            @include('layouts.partials.sidebar', ['theme' => $theme])
            <div class="content-wrapper">
                <div class="content-inner">
                    @include('layouts.partials.page-header')
                    <div class="content pt-0">{{ $slot }}</div>
                    @include('layouts.partials.footer')
                </div>
            </div>
        </div>
    @endif

    @if (Route::has('admin.notification.index'))
        @include('layouts.partials.notification')
    @endif
    @include('layouts.partials.right-sidebar')

    @stack('modals')

    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>
    @stack('scripts')

    <a href="#" class="btn btn-sm btn-light btn-floating-settings" data-bs-toggle="offcanvas"
        data-bs-target="#demo_config">
        <i class="ph-gear"></i>
    </a>

</body>

</html>
