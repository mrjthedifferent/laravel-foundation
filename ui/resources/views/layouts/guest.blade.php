<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $theme['direction'] }}"
    data-theme="{{ $theme['colorMode'] === 'dark' ? 'dark' : 'light' }}"
    data-color-palette="{{ $theme['palette'] }}"
    data-width="{{ $theme['contentWidth'] ?? 'fluid' }}" data-density="{{ $theme['density'] ?? 'comfortable' }}"
    data-radius="{{ $theme['radius'] ?? 'rounded' }}">

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

<body class="auth-backdrop">

    <div class="fd-auth">
        {{-- The brand panel, drawn from config/foundation-login.php (Settings → Login page) --}}
        @include('layouts.partials.auth-aside')

        <main class="fd-auth-main">
            {{-- On phones the brand panel is hidden, so the brand sits above the form --}}
            <div class="fd-auth-mobile-brand">@include('layouts.partials.auth-brand')</div>

            <div class="login-form">
                {{ $slot }}

                @auth
                    @if (Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}" class="text-center mt-6">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">
                                <i class="ph ph-sign-out"></i>{{ __('foundation::foundation.layout.logout') }}
                            </button>
                        </form>
                    @endif
                @endauth
            </div>
        </main>
    </div>

    @stack('modals')

    <script src="{{ foundation_asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ foundation_asset('assets/vendor/select2/select2.min.js') }}"></script>
    @stack('scripts')
</body>

</html>
