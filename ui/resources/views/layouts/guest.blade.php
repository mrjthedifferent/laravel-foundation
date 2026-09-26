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

<body class="auth-backdrop">

    <div class="fd-auth">
        {{-- The brand panel: hidden on phones, where the form is all that matters. --}}
        <aside class="fd-auth-aside">
            <a href="{{ url('/') }}" class="fd-brand">
                @if (mailLogoUrl())
                    <img src="{{ mailLogoUrl() }}" alt="">
                @endif
                <span class="fd-brand-name">{{ mailAppName() }}</span>
            </a>

            <div>
                <p class="fd-auth-quote">{{ __('foundation::foundation.auth.tagline') }}</p>
                <p class="fd-auth-quote-meta">{{ __('foundation::foundation.auth.tagline_meta', ['app' => mailAppName()]) }}</p>
            </div>

            <div class="fs-xs fd-auth-copyright">
                &copy; @if(date('Y') == config('app.copyright_year', date('Y'))) {{ date('Y') }} @else {{ config('app.copyright_year') }} - {{ date('Y') }} @endif {{ appName() }}
            </div>
        </aside>

        <main class="fd-auth-main">
            <div class="login-form">
                {{ $slot }}

                @auth
                    @if (Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}" class="text-center mt-4">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">
                                <i class="ph-sign-out"></i>{{ __('foundation::foundation.layout.logout') }}
                            </button>
                        </form>
                    @endif
                @endauth
            </div>
        </main>
    </div>

    @stack('modals')

    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>
    @stack('scripts')
</body>

</html>
