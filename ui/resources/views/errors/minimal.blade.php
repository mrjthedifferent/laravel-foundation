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

    <script src="{{ asset('assets/js/foundation.js') }}"></script>
</head>
<body class="auth-backdrop">
<div class="fd-error">
    <div class="fd-error-code">@yield('code', '404')</div>
    <h1 class="fd-error-title">@yield('message', __('foundation::foundation.errors.service_unavailable'))</h1>
    <p class="fd-error-text">@yield('description', __('foundation::foundation.errors.description'))</p>

    <a href="/" class="btn btn-primary">
        <i class="ph-house"></i>{{ __('foundation::foundation.errors.return_home') }}
    </a>
</div>
</body>
</html>
