@php
$theme = $theme ?? [];
$themeNavbarColor = $theme['navbarColor'] ?? $themeNavbarColor ?? config('settings.theme_navbar_color.value', 'dark');
$themeNavbarBg = $theme['navbarBg'] ?? $themeNavbarBg ?? config('settings.theme_navbar_bg.value', '');
$themeLayout = $theme['layout'] ?? $themeLayout ?? config('settings.theme_layout.value', '1');

$navbarColorClass = match($themeNavbarColor) {
'light' => 'navbar-light',
'primary' => 'navbar-primary',
default => 'navbar-dark',
};
$navbarBorderClass = ($themeNavbarColor === 'light')
? 'border-bottom'
: 'border-bottom border-bottom-white border-opacity-10';
$navbarShadow = ($themeLayout === '2') ? 'shadow' : '';

// Hex custom background — validate and build inline style
$navbarBgStyle = '';
if (!empty($themeNavbarBg) && preg_match('/^#[0-9a-fA-F]{6}$/', $themeNavbarBg)) {
$navbarBgStyle = 'background-color:' . $themeNavbarBg . ' !important;';
}
// Search bar color theme: light navbar needs light-mode search, dark/primary need dark
$searchColorTheme = ($themeNavbarColor === 'light') ? 'light' : 'dark';
@endphp
<!-- Main navbar -->
<div class="navbar {{ $navbarColorClass }} navbar-expand-lg navbar-static {{ $navbarBorderClass }} {{ $navbarShadow }}"
    id="main-navbar"
    @if($navbarBgStyle) style="{{ $navbarBgStyle }}" @endif>
    <div class="container-fluid">

        <div class="d-flex d-lg-none me-2">
            <button type="button" class="navbar-toggler sidebar-mobile-main-toggle rounded-pill">
                <i class="ph-list"></i>
            </button>
        </div>

        <div class="navbar-brand flex-1 flex-lg-0">
            <a href="{{ route('admin.dashboard') }}" class="d-inline-flex align-items-center">
                @if (mailLogoUrl())
                <img src="{{ mailLogoUrl() }}" alt="{{ mailAppName() }}">
                @else
                <span class="fw-semibold">{{ mailAppName() }}</span>
                @endif
            </a>
        </div>

        <ul class="nav flex-row">
            <li class="nav-item d-lg-none">
                <a href="#navbar_search" class="navbar-nav-link navbar-nav-link-icon rounded-pill" data-bs-toggle="collapse">
                    <i class="ph-magnifying-glass"></i>
                </a>
            </li>
        </ul>

        <div class="navbar-collapse justify-content-center flex-lg-1 order-2 order-lg-1 collapse" id="navbar_search">
            <div class="navbar-search flex-fill position-relative mt-2 mt-lg-0 mx-lg-3">
                <div class="form-control-feedback form-control-feedback-start flex-grow-1" data-color-theme="{{ $searchColorTheme }}">
                    <!-- Added ID globalSearchInput -->
                    <input type="text" id="globalSearchInput" class="form-control bg-transparent rounded-pill" placeholder="Search" data-bs-toggle="dropdown">
                    <div class="form-control-feedback-icon">
                        <i class="ph-magnifying-glass"></i>
                    </div>
                    <!-- Added ID globalSearchDropdown -->
                    <div class="dropdown-menu w-100" id="globalSearchDropdown" data-color-theme="light">
                        <button type="button" class="dropdown-item">
                            <div class="text-center w-32px me-3">
                                <i class="ph-magnifying-glass"></i>
                            </div>
                            <span>Search everywhere</span>
                        </button>
                    </div>
                </div>

                <a href="#" class="navbar-nav-link align-items-center justify-content-center w-40px h-32px rounded-pill position-absolute end-0 top-50 translate-middle-y p-0 me-1"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <i class="ph-faders-horizontal"></i>
                </a>

                <div class="dropdown-menu w-100 p-3">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0">Search options</h6>
                        <a href="#" class="text-body rounded-pill ms-auto">
                            <i class="ph-clock-counter-clockwise"></i>
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="d-block form-label">Category</label>
                        <label class="form-check form-check-inline">
                            <input type="radio" name="globalSearchCategory" value="all" class="form-check-input" checked>
                            <span class="form-check-label">All</span>
                        </label>
                        <label class="form-check form-check-inline">
                            <input type="radio" name="globalSearchCategory" value="users" class="form-check-input">
                            <span class="form-check-label">Users</span>
                        </label>
                    </div>
                    <div class="d-flex">
                        <button type="button" class="btn btn-light" id="globalSearchResetBtn">Reset</button>
                        <div class="ms-auto">
                            <button type="button" class="btn btn-light" data-bs-toggle="dropdown">Cancel</button>
                            <button type="button" class="btn btn-primary ms-2" id="globalSearchApplyBtn">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav flex-row justify-content-end order-1 order-lg-2">

            @if(config('broadcasting.default') === 'reverb' && filled(config('reverb.apps.apps.0.key')))
            <li class="nav-item dropdown d-flex align-items-center">
                <a href="#" id="online-user-count" class="online-indicator d-inline-flex align-items-center gap-1 ms-2 px-2 py-1 rounded-pill {{ $themeNavbarColor === 'light' ? 'bg-dark bg-opacity-10' : 'bg-white bg-opacity-10' }} text-body text-decoration-none"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Users online">
                    <span class="position-relative d-flex">
                        <i class="ph-users-three"></i>
                        <span class="online-pulse position-absolute top-0 start-100 translate-middle rounded-circle bg-success"></span>
                    </span>
                    <span class="online-count fw-semibold small">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end py-2" id="online-users-dropdown">
                    <h6 class="dropdown-header py-1">
                        <i class="ph-users-three me-2"></i>Online now
                    </h6>
                    <div class="dropdown-divider my-1"></div>
                    <div id="online-users-list" class="px-3 py-2 text-muted small" style="max-height: 280px; overflow-y: auto;">
                        <span class="online-users-empty">No users online</span>
                        <div id="online-users-items" class="d-none"></div>
                        <div id="online-users-more" class="small text-muted pt-1 mt-1 border-top d-none"></div>
                    </div>
                </div>
            </li>
            @endif
            @if (Route::has('admin.notification.index'))
            <li class="nav-item">
                <a href="#" class="navbar-nav-link navbar-nav-link-icon rounded-pill"
                    data-bs-toggle="offcanvas" data-bs-target="#notifications">
                    <i class="ph-bell"></i>
                    <span id="notification-count"
                        class="badge bg-yellow text-black position-absolute top-0 end-0 translate-middle-top zindex-1 rounded-pill mt-1 me-1">
                        0
                    </span>
                </a>
            </li>
            @endif

            <li class="nav-item nav-item-dropdown-lg dropdown ms-lg-2">
                <a href="#" class="navbar-nav-link align-items-center p-1" data-bs-toggle="dropdown">
                    <img src="{{ Auth::user()?->image ?: asset('images/person.png') }}"
                        class="w-32px h-32px" alt="{{ Auth::user()?->name }}">
                    <span class="d-none d-lg-inline-block mx-lg-2">{{ Auth::user()?->name }}</span>
                </a>

                <div class="dropdown-menu dropdown-menu-end">
                    @if (Route::has('admin.profile.edit'))
                    <a href="{{ route('admin.profile.edit') }}" class="dropdown-item">
                        <i class="ph-user-circle me-2"></i>
                        My profile
                    </a>
                    @endif
                    @stack('navbar_user_menu')
                    @if(app(\Mrj\Foundation\Contracts\ImpersonationContext::class)->isImpersonating() && Route::has('admin.impersonation.leave'))
                        <div class="dropdown-divider"></div>
                        <x-dropdown-link :url="route('admin.impersonation.leave')"
                            data-text="Return to your own account?"
                            class="swal-post">
                            <i class="ph-user-switch me-2"></i>Return to my account
                        </x-dropdown-link>
                    @endif
                    @if (Route::has('logout'))
                    <div class="dropdown-divider"></div>
                    <x-dropdown-link :url="route('logout')"
                        data-text="Are you sure you want to logout?"
                        class="swal-post">
                        <i class="ph-sign-out me-2"></i>{{ __('Logout') }}
                    </x-dropdown-link>
                    @endif
                </div>
            </li>

        </ul>
    </div>
</div>
<!-- /main navbar -->