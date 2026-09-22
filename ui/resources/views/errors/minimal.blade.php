@php
    $theme = $theme ?? app(\Mrj\Foundation\Services\ThemeResolver::class)->resolveForGuest();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ $theme['direction'] }}"
      data-bs-theme="{{ $theme['colorMode'] === 'dark' ? 'dark' : 'light' }}"
      data-color-palette="{{ $theme['palette'] }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        window.__THEME__ = @json($theme['windowTheme']);
    </script>

    <title>@yield('title', config('app.name', 'App')) — {{ config('app.name', 'App') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $theme['favicon'] }}">

    @include('layouts.partials.head-styles', ['theme' => $theme])

    <style>
        :root {
            --custom-primary: {{ $theme['customColor'] }};
            --custom-primary-rgb: {{ $theme['ccR'] }}, {{ $theme['ccG'] }}, {{ $theme['ccB'] }};
            --custom-primary-dark: {{ $theme['customColorDark'] }};
            --custom-sidebar-bg: {{ !empty($theme['sidebarColorCustom']) ? $theme['sidebarColorCustom'] : 'transparent' }};
        }
    </style>

    <script src="{{ asset('assets/js/foundation.js') }}"></script>
</head>
<body class="auth-backdrop">
<!-- Page content -->
<div class="page-content">
    <!-- Main content -->
    <div class="content-wrapper">
        <div class="content-inner">
            <div class="content d-flex justify-content-center align-items-center">
                <!-- Container -->
                <div class="flex-fill">
                    <!-- Error title -->
                    <div class="text-center mb-4">
                        <div class="error-title mb-3">@yield('code', '404')</div>
                        <h6 class="w-md-25 mx-md-auto">
                            @yield('message', __('foundation::foundation.errors.service_unavailable'))
                        </h6>
                    </div>
                    <!-- /error title -->

                    <!-- Error content -->
                    <div class="text-center">
                        <a href="/" class="btn btn-primary">
                            <i class="ph-house me-2"></i>
                            {{ __('foundation::foundation.errors.return_home') }}
                        </a>
                    </div>
                    <!-- /error content -->
                </div>
                <!-- /container -->
            </div>
        </div>
    </div>
    <!-- /main content -->
</div>
<!-- /page content -->
</body>
</html>
