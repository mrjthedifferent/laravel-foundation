@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

$current_route = Route::currentRouteName();

$theme = $theme ?? [];
$themeSidebarColor = $theme['sidebarColor'] ?? config('settings.theme_sidebar_color.value', 'light');
$themeSidebarType = $theme['sidebarType'] ?? config('settings.theme_sidebar_type.value', 'default');

$sidebarColorClass = $themeSidebarColor === 'dark' ? 'sidebar-dark' : 'sidebar-light';
$sidebarTypeClass = $themeSidebarType === 'mini' ? 'sidebar-main-resized' : '';

$user = Auth::user();
$appName = mailAppName();
$logo = mailLogoUrl();
$monogram = Str::upper(Str::substr(trim($appName), 0, 1));
$userInitials = Str::upper(Str::substr(trim((string) $user?->name), 0, 1));
$userRole = $user?->isSuperAdmin()
    ? __('foundation::foundation.common.super_admin')
    : display_label($user?->roles->first()?->name ?? '');
@endphp

<!-- Main sidebar -->
<div class="sidebar {{ $sidebarColorClass }} sidebar-main sidebar-expand-lg {{ $sidebarTypeClass }}" id="main-sidebar">

    <!-- Brand -->
    <div class="sidebar-header">
        <div class="sidebar-section">
            <div class="sidebar-section-body d-flex justify-content-between">
                <a href="{{ route('admin.dashboard') }}" class="fd-brand">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="">
                    @else
                        <span class="fd-brand-mark">{{ $monogram }}</span>
                    @endif
                    <span class="fd-brand-name">{{ $appName }}</span>
                </a>

                <button type="button"
                    class="btn btn-ghost btn-icon btn-sm sidebar-main-resize sidebar-resize-hide d-none d-lg-inline-flex"
                    title="{{ __('foundation::foundation.sidebar.toggle') }}"
                    aria-label="{{ __('foundation::foundation.sidebar.toggle') }}">
                    <i class="ph-sidebar-simple"></i>
                </button>

                <button type="button" class="btn btn-ghost btn-icon btn-sm sidebar-mobile-main-toggle d-lg-none"
                    aria-label="{{ __('foundation::foundation.common.close') }}">
                    <i class="ph-x"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- /brand -->

    <!-- Main navigation: the only part of the sidebar that scrolls -->
    <div class="sidebar-content">
        <div class="sidebar-section">
            <ul class="nav nav-sidebar" id="navbar-nav" data-nav-type="accordion">

                <li class="nav-item-header pt-0">
                    <div class="sidebar-resize-hide">{{ __('foundation::foundation.sidebar.main') }}</div>
                    <i class="ph-dots-three sidebar-resize-show"></i>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}"
                        class="nav-link @if($current_route === 'admin.dashboard') active @endif">
                        <i class="ph-house"></i>
                        <span>{{ __('foundation::foundation.layout.dashboard') }}</span>
                    </a>
                </li>

                {{-- Parents are business areas, pages sit directly under them: see config/sidebar.php --}}
                @foreach (app(\Mrj\Foundation\Support\SidebarMenu::class)->forUser(auth()->user(), $current_route) as $group)
                @if ($group['single'])
                <li class="nav-item">
                    <a href="{{ $group['items'][0]['href'] }}" class="nav-link @if ($group['open']) active @endif" @if ($group['items'][0]['target']) target="{{ $group['items'][0]['target'] }}" @endif>
                        <i class="{{ $group['icon'] }}"></i>
                        <span>{{ $group['label'] }}</span>
                    </a>
                </li>
                @continue
                @endif
                <li class="nav-item nav-item-submenu @if ($group['open']) nav-item-open @endif">
                    <a href="#" class="nav-link @if ($group['open']) active @endif">
                        <i class="{{ $group['icon'] }}"></i>
                        <span>{{ $group['label'] }}</span>
                    </a>
                    <ul class="nav-group-sub collapse @if ($group['open']) show @endif" data-submenu-title="{{ $group['label'] }}">
                        @foreach ($group['items'] as $item)
                        <li class="nav-item">
                            <a href="{{ $item['href'] }}" class="nav-link @if ($item['active']) active @endif" @if ($item['target']) target="{{ $item['target'] }}" @endif>
                                <i class="{{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </li>
                @endforeach

                {{-- A module that still ships its own partial is honoured (none do today). --}}
                @foreach (\Nwidart\Modules\Facades\Module::allEnabled() as $module)
                @php $menuView = strtolower($module->getName()) . '::partials.menu'; @endphp
                @if (view()->exists($menuView))
                @include($menuView)
                @endif
                @endforeach

            </ul>
        </div>
    </div>
    <!-- /main navigation -->

    @if ($user)
    <!-- Signed-in user -->
    <div class="sidebar-footer">
        <a href="{{ Route::has('admin.profile.edit') ? route('admin.profile.edit') : '#' }}" class="fd-sidebar-user">
            @if ($user->image)
                <img src="{{ $user->image }}" class="fd-avatar" alt="{{ $user->name }}">
            @else
                <span class="fd-avatar">{{ $userInitials }}</span>
            @endif
            <span class="min-width-0 sidebar-resize-hide">
                <span class="fd-sidebar-name text-truncate d-block">{{ $user->name }}</span>
                <span class="fs-xs text-truncate d-block">{{ $userRole }}</span>
            </span>
        </a>
    </div>
    <!-- /signed-in user -->
    @endif

</div>
<!-- /main sidebar -->
