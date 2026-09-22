@php
    $theme = $theme ?? [];
    $themeNavbarColor = $theme['navbarColor'] ?? $themeNavbarColor ?? config('settings.theme_navbar_color.value', 'dark');
    $themeNavbarBg    = $theme['navbarBg'] ?? $themeNavbarBg ?? config('settings.theme_navbar_bg.value', '');

    $navbarColorClass = match($themeNavbarColor) {
        'light'   => 'navbar-light',
        'primary' => 'navbar-primary',
        default   => 'navbar-dark',
    };
    $navbarBorderClass = ($themeNavbarColor === 'light')
        ? 'border-bottom'
        : 'border-bottom border-bottom-white border-opacity-10';

    $navbarBgStyle = '';
    if (!empty($themeNavbarBg) && preg_match('/^#[0-9a-fA-F]{6}$/', $themeNavbarBg)) {
        $navbarBgStyle = 'background-color:' . $themeNavbarBg . ' !important;';
    }
@endphp
<!-- Main navbar -->
<div class="navbar {{ $navbarColorClass }} {{ $navbarBorderClass }} navbar-static py-2"
     @if($navbarBgStyle) style="{{ $navbarBgStyle }}" @endif>
    <div class="container-fluid">
        <div class="navbar-brand">
            <a href="{{ route('admin.dashboard') }}" class="d-inline-flex align-items-center">
                @if (mailLogoUrl())
                <img src="{{ mailLogoUrl() }}" class="d-none d-sm-inline-block h-24px ms-3" alt="">
                @else
                <span class="fw-semibold ms-3">{{ mailAppName() }}</span>
                @endif
            </a>
        </div>

        <div class="d-flex justify-content-end align-items-center ms-auto">
            <ul class="navbar-nav flex-row">
                @auth
                @if (Route::has('logout'))
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="navbar-nav-link navbar-nav-link-icon rounded ms-1 border-0 bg-transparent">
                            <div class="d-flex align-items-center mx-md-1">
                                <i class="ph-sign-out"></i>
                                <span class="d-none d-md-inline-block ms-2">{{ __('foundation::foundation.layout.logout') }}</span>
                            </div>
                        </button>
                    </form>
                </li>
                @endif
                @endauth
            </ul>
        </div>
    </div>
</div>
<!-- /main navbar -->
