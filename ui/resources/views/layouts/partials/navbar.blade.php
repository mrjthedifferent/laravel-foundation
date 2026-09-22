<!-- Main navbar -->
<div class="navbar navbar-expand-lg navbar-static" id="main-navbar">
    <div class="container-fluid">

        <div class="navbar-group">
            <button type="button" class="navbar-toggler sidebar-mobile-main-toggle d-lg-none"
                aria-label="{{ __('foundation::foundation.sidebar.navigation') }}">
                <i class="ph-list"></i>
            </button>
        </div>

        {{-- One search entry point: it opens the ⌘K palette (resources/js/navigation-search.js). --}}
        <button type="button" class="fd-search-trigger" id="globalSearchTrigger"
            @if (Route::has('admin.global-search')) data-search-url="{{ route('admin.global-search') }}" @endif
            data-search-strings="{{ json_encode([
                'label' => __('foundation::foundation.search.label'),
                'placeholder' => __('foundation::foundation.navbar.search_placeholder'),
                'pages' => __('foundation::foundation.search.pages'),
                'people' => __('foundation::foundation.search.people'),
                'empty' => __('foundation::foundation.search.empty'),
                'navigate' => __('foundation::foundation.search.navigate'),
                'open' => __('foundation::foundation.search.open'),
                'dismiss' => __('foundation::foundation.search.dismiss'),
            ], JSON_UNESCAPED_UNICODE) }}">
            <i class="ph-magnifying-glass"></i>
            <span>{{ __('foundation::foundation.navbar.search_placeholder') }}</span>
            <span class="fd-kbd" data-kbd-mod>Ctrl</span><span class="fd-kbd">K</span>
        </button>

        <ul class="nav navbar-group">

            @if(config('broadcasting.default') === 'reverb' && filled(config('reverb.apps.apps.0.key')))
            <li class="nav-item dropdown d-none d-sm-flex align-items-center">
                <a href="#" id="online-user-count" class="navbar-nav-link online-indicator rounded-pill gap-2"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    title="{{ __('foundation::foundation.navbar.users_online') }}">
                    <span class="fd-status is-success online-pulse"></span>
                    <span class="online-count fw-semibold">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end py-2" id="online-users-dropdown">
                    <h6 class="dropdown-header">
                        <i class="ph-users-three me-2"></i>{{ __('foundation::foundation.navbar.online_now') }}
                    </h6>
                    <div class="dropdown-divider my-1"></div>
                    <div id="online-users-list" class="px-3 py-2 text-muted fs-sm fd-scroll-y">
                        <span class="online-users-empty">{{ __('foundation::foundation.navbar.no_users_online') }}</span>
                        <div id="online-users-items" class="d-none"></div>
                        <div id="online-users-more" class="fs-sm text-muted pt-1 mt-1 border-top d-none"></div>
                    </div>
                </div>
            </li>
            @endif

            @if (Route::has('admin.notification.index'))
            <li class="nav-item">
                <a href="#" class="navbar-nav-link navbar-nav-link-icon" data-bs-toggle="offcanvas"
                    data-bs-target="#notifications" aria-label="{{ __('foundation::foundation.navbar.notifications') }}">
                    <i class="ph-bell"></i>
                    <span id="notification-count" class="fd-notify-dot" data-count="0"></span>
                </a>
            </li>
            @endif

            <li class="nav-item dropdown">
                <a href="#" class="navbar-nav-link px-1" data-bs-toggle="dropdown"
                    aria-label="{{ __('foundation::foundation.navbar.my_profile') }}">
                    @if (Auth::user()?->image)
                        <img src="{{ Auth::user()->image }}" class="fd-avatar" alt="{{ Auth::user()->name }}">
                    @else
                        <span class="fd-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) Auth::user()?->name, 0, 1)) }}</span>
                    @endif
                </a>

                <div class="dropdown-menu dropdown-menu-end">
                    <div class="dropdown-header text-truncate">{{ Auth::user()?->email }}</div>
                    @if (Route::has('admin.profile.edit'))
                    <a href="{{ route('admin.profile.edit') }}" class="dropdown-item">
                        <i class="ph-user-circle"></i>
                        {{ __('foundation::foundation.navbar.my_profile') }}
                    </a>
                    @endif
                    @stack('navbar_user_menu')
                    @if(app(\Mrj\Foundation\Contracts\ImpersonationContext::class)->isImpersonating() && Route::has('admin.impersonation.leave'))
                        <div class="dropdown-divider"></div>
                        <x-dropdown-link :url="route('admin.impersonation.leave')"
                            data-text="{{ __('foundation::foundation.layout.return_to_own_account_confirm') }}"
                            class="swal-post">
                            <i class="ph-user-switch"></i>{{ __('foundation::foundation.layout.return_to_my_account') }}
                        </x-dropdown-link>
                    @endif
                    @if (Route::has('logout'))
                    <div class="dropdown-divider"></div>
                    <x-dropdown-link :url="route('logout')"
                        data-text="{{ __('foundation::foundation.layout.logout_confirm') }}"
                        class="swal-post">
                        <i class="ph-sign-out"></i>{{ __('foundation::foundation.layout.logout') }}
                    </x-dropdown-link>
                    @endif
                </div>
            </li>

        </ul>
    </div>
</div>
<!-- /main navbar -->
