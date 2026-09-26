<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $theme['direction'] }}"
    data-bs-theme="{{ $theme['colorMode'] === 'dark' ? 'dark' : 'light' }}"
    data-color-palette="{{ $theme['palette'] }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.partials.head-scripts', ['theme' => $theme])

    <title>{{ appName() }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $theme['favicon'] }}">

    @include('layouts.partials.head-styles', ['theme' => $theme])

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
    @stack('head_scripts')
</head>

<body>

    @include('layouts.partials.impersonation-banner')

    {{-- The shell fills the viewport: the sidebar, the navbar and the footer stay put,
         and only the content column scrolls. --}}
    <div class="page-content">
        @include('layouts.partials.sidebar', ['theme' => $theme])
        <div class="content-wrapper">
            @include('layouts.partials.navbar', ['theme' => $theme])
            <div class="content-inner">
                @include('layouts.partials.page-header')
                <main class="content pt-0">{{ $slot }}</main>
            </div>
            @include('layouts.partials.footer')
        </div>
    </div>

    @if (Route::has('admin.notification.index'))
        @include('layouts.partials.notification')
    @endif
    @include('layouts.partials.right-sidebar')

    @stack('modals')

    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>
    @stack('scripts')

    <button type="button" class="btn btn-light btn-icon btn-floating-settings" data-bs-toggle="offcanvas"
        data-bs-target="#demo_config" aria-label="{{ __('foundation::foundation.theme_config.title') }}">
        <i class="ph-gear"></i>
    </button>

</body>

</html>
