<!-- Main navbar -->
<div class="navbar navbar-static" id="main-navbar">
    <div class="container-fluid">

        <div class="navbar-group">
            <button type="button" class="navbar-toggler sidebar-mobile-main-toggle lg:hidden"
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

            {{-- Only super admins get past EnsurePanelIsAvailable, so only they see this. --}}
            @if (config('settings.maintenance_mode.value'))
            <li class="nav-item flex items-center">
                <a href="{{ Route::has('admin.settings.index') ? route('admin.settings.index') : '#' }}"
                    class="badge badge-warning text-strong no-underline"
                    title="{{ __('foundation::foundation.navbar.maintenance_on_help') }}">
                    <i class="ph-wrench"></i> {{ __('foundation::foundation.navbar.maintenance_on') }}
                </a>
            </li>
            @endif

            @if(config('broadcasting.default') === 'reverb' && filled(config('reverb.apps.apps.0.key')))
            <li class="nav-item dropdown hidden sm:flex items-center">
                <a href="#" id="online-user-count" class="navbar-nav-link online-indicator rounded-full gap-2"
                    data-fd-toggle="dropdown" data-fd-auto-close="outside"
                    title="{{ __('foundation::foundation.navbar.users_online') }}">
                    <span class="fd-status is-success online-pulse"></span>
                    <span class="online-count font-semibold">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end py-2" id="online-users-dropdown">
                    <h6 class="dropdown-header">
                        <i class="ph-users-three me-2"></i>{{ __('foundation::foundation.navbar.online_now') }}
                    </h6>
                    <div class="dropdown-divider my-1"></div>
                    <div id="online-users-list" class="px-4 py-2 text-muted text-sm fd-scroll-y">
                        <span class="online-users-empty">{{ __('foundation::foundation.navbar.no_users_online') }}</span>
                        <div id="online-users-items" class="hidden"></div>
                        <div id="online-users-more" class="text-sm text-muted pt-1 mt-1 border-t hidden"></div>
                    </div>
                </div>
            </li>
            @endif

            @if (Route::has('admin.notification.index'))
            <li class="nav-item">
                <a href="#" class="navbar-nav-link navbar-nav-link-icon" data-fd-toggle="offcanvas"
                    data-fd-target="#notifications" aria-label="{{ __('foundation::foundation.navbar.notifications') }}">
                    <i class="ph-bell"></i>
                    <span id="notification-count" class="fd-notify-dot" data-count="0"></span>
                </a>
            </li>
            @endif

            <li class="nav-item dropdown">
                <a href="#" class="navbar-nav-link px-1" data-fd-toggle="dropdown"
                    aria-label="{{ __('foundation::foundation.navbar.my_profile') }}">
                    @if (Auth::user()?->image)
                        <img src="{{ Auth::user()->image }}" class="fd-avatar" alt="{{ Auth::user()->name }}">
                    @else
                        <span class="fd-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) Auth::user()?->name, 0, 1)) }}</span>
                    @endif
                </a>

                <div class="dropdown-menu dropdown-menu-end">
                    <div class="dropdown-header truncate">{{ Auth::user()?->email }}</div>
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
