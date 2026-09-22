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

    <title>{{ config('app.name', 'App') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $theme['favicon'] }}">

    @include('layouts.partials.head-styles', ['theme' => $theme])

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
    @stack('head_scripts')
</head>

<body class="auth-backdrop">

    @include('layouts.partials.auth-navbar')

    <div class="page-content">
        <div class="content-wrapper">
            <div class="content-inner">
                {{ $slot }}
            </div>
        </div>
    </div>

    @include('layouts.partials.footer')
    @include('layouts.partials.right-sidebar')

    @stack('modals')

    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>
    @stack('scripts')
</body>

</html>
