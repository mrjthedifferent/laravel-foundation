@php
use Illuminate\Support\Facades\Route;
$all_permission = allPermissions();
$current_route = Route::currentRouteName();

$theme = $theme ?? [];
$themeSidebarColor = $theme['sidebarColor'] ?? $themeSidebarColor ?? config('settings.theme_sidebar_color.value', 'dark');
$themeSidebarColorCustom = $theme['sidebarColorCustom'] ?? $themeSidebarColorCustom ?? config('settings.theme_sidebar_color_custom.value', '');
$themeSidebarType = $theme['sidebarType'] ?? $themeSidebarType ?? config('settings.theme_sidebar_type.value', 'default');
$themeLayout = $theme['layout'] ?? $themeLayout ?? config('settings.theme_layout.value', '1');

if (!empty($themeSidebarColorCustom) && !preg_match('/^#[0-9a-fA-F]{6}$/', $themeSidebarColorCustom)) {
    $themeSidebarColorCustom = '';
}

// Color class: use sidebar-custom when a hex is provided, else sidebar-dark|sidebar-light|sidebar-primary
if (!empty($themeSidebarColorCustom)) {
$sidebarColorClass = 'sidebar-custom';
$sidebarBgStyle = 'background-color:' . $themeSidebarColorCustom . ' !important;';
} else {
$sidebarColorClass = match($themeSidebarColor) {
'light' => 'sidebar-light',
'primary' => 'sidebar-primary',
default => 'sidebar-dark',
};
$sidebarBgStyle = '';
}

// Button style: white for dark/primary/custom, secondary for light
$btnStyle = ($themeSidebarColor === 'light' && empty($themeSidebarColorCustom)) ? 'btn-flat-secondary' : 'btn-flat-white';

// Type class: (default = nothing extra) | sidebar-main-resized (mini)
$sidebarTypeClass = ($themeSidebarType === 'mini') ? 'sidebar-main-resized' : '';

// Layout 3: detached sidebar
$sidebarLayoutClass = ($themeLayout === '3') ? 'align-self-start' : '';

// Button style: white for dark/primary/custom, secondary for light
$btnStyle = ($themeSidebarColor === 'light' && empty($themeSidebarColorCustom)) ? 'btn-flat-secondary' : 'btn-flat-white';
@endphp

<!-- Main sidebar -->
<div class="sidebar {{ $sidebarColorClass }} sidebar-main sidebar-expand-lg {{ $sidebarTypeClass }} {{ $sidebarLayoutClass }}"
    id="main-sidebar"
    @if($sidebarBgStyle) style="{{ $sidebarBgStyle }}" @endif>

    <!-- Sidebar content -->
    <div class="sidebar-content">

        <!-- Sidebar header -->
        <div class="sidebar-section">
            <div class="sidebar-section-body d-flex justify-content-center">
                <h5 class="sidebar-resize-hide flex-grow-1 my-auto">Navigation </h5>

                <div>
                    <button type="button" class="btn {{ $btnStyle }} btn-icon btn-sm rounded-pill border-transparent sidebar-control sidebar-search-trigger sidebar-resize-hide d-none d-lg-inline-flex" id="sidebarSearchBtn" data-bs-popup="tooltip" data-bs-placement="bottom" data-toggle="tooltip" data-placement="top" title="Search (Ctrl+K)">
                        <i class="ph-magnifying-glass"></i>
                    </button>

                    <button type="button" class="btn {{ $btnStyle }} btn-icon btn-sm rounded-pill border-transparent sidebar-control sidebar-main-resize d-none d-lg-inline-flex">
                        <i class="ph-arrows-left-right"></i>
                    </button>

                    <button type="button" class="btn {{ $btnStyle }} btn-icon btn-sm rounded-pill border-transparent sidebar-mobile-main-toggle d-lg-none">
                        <i class="ph-x"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- /sidebar header -->

        <!-- Main navigation -->
        <div class="sidebar-section">
            <ul class="nav nav-sidebar" id="navbar-nav" data-nav-type="accordion">

                <!-- Main -->
                <li class="nav-item-header pt-0">
                    <div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Main</div>
                    <i class="ph-dots-three sidebar-resize-show"></i>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}"
                        class="nav-link @if($current_route === 'admin.dashboard') active @endif">
                        <i class="ph-house"></i>
                        <span>Dashboard</span>
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
                                <i class="{{ $item['icon'] }} me-1"></i>
                                {{ $item['label'] }}
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
        <!-- /main navigation -->

    </div>
    <!-- /sidebar content -->

</div>
<!-- /main sidebar -->