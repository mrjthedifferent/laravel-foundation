@php
    $theme = $theme ?? [];
    $rsColorMode   = $theme['colorMode'] ?? $themeColorMode ?? config('settings.theme_color_mode.value', 'light');
    $rsDirection   = $theme['direction'] ?? $themeDirection ?? config('settings.theme_direction.value', 'ltr');
    $rsPalette     = $theme['palette'] ?? $themePalette ?? config('settings.theme_color_palette.value', 'blue');
    $rsLayout      = $theme['layout'] ?? $themeLayout ?? config('settings.theme_layout.value', '1');
    $rsSidebarClr  = $theme['sidebarColor'] ?? $themeSidebarColor ?? config('settings.theme_sidebar_color.value', 'dark');
    $rsSidebarType = $theme['sidebarType'] ?? $themeSidebarType ?? config('settings.theme_sidebar_type.value', 'default');
    $rsNavbarColor = $theme['navbarColor'] ?? $themeNavbarColor ?? config('settings.theme_navbar_color.value', 'dark');
    $rsFont        = $theme['fontFamily'] ?? $themeFontFamily ?? config('settings.theme_font_family.value', 'inter');
@endphp
<!-- Demo config -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="demo_config">

    <div class="offcanvas-header border-bottom py-0">
        <h5 class="offcanvas-title py-3">Theme configuration</h5>
        <button type="button" class="btn btn-light btn-sm btn-icon border-transparent rounded-pill"
                data-bs-dismiss="offcanvas">
            <i class="ph-x"></i>
        </button>
    </div>

    <div class="offcanvas-body">

        {{-- ── Active Settings Summary ─────────────────────────────────── --}}
        <div class="mb-3 p-2 border rounded bg-body-tertiary bg-opacity-50 fs-sm">
            <div class="d-flex flex-wrap gap-1">
                <span class="badge bg-secondary">Layout {{ $rsLayout }}</span>
                <span class="badge bg-secondary">{{ ucfirst($rsColorMode) }}</span>
                <span class="badge bg-secondary">{{ strtoupper($rsDirection) }}</span>
                <span class="badge bg-secondary">{{ ucfirst($rsPalette) }}</span>
                <span class="badge bg-secondary">Sidebar: {{ ucfirst($rsSidebarClr) }}/{{ ucfirst($rsSidebarType) }}</span>
                <span class="badge bg-secondary">Navbar: {{ ucfirst($rsNavbarColor) }}</span>
                <span class="badge bg-secondary">Font: {{ ucfirst($rsFont) }}</span>
            </div>
        </div>

        {{-- ── Color mode (live session toggle) ───────────────────────── --}}
        <div class="fw-semibold mb-2">Color mode <span class="text-muted fw-normal fs-sm">(session override)</span></div>
        <div class="list-group mb-3">
            @foreach(['light' => ['icon'=>'ph-sun','label'=>'Light theme','desc'=>'Set light theme or reset to default'],
                       'dark'  => ['icon'=>'ph-moon','label'=>'Dark theme','desc'=>'Switch to dark theme'],
                       'auto'  => ['icon'=>'ph-translate','label'=>'Auto theme','desc'=>'Set theme based on system mode']] as $val => $opt)
            <label class="list-group-item list-group-item-action form-check border-width-1 rounded mb-2 @if($val === $rsColorMode) rs-item-selected @endif">
                <div class="d-flex flex-fill my-1">
                    <div class="form-check-label d-flex me-2">
                        <i class="{{ $opt['icon'] }} ph-lg me-3"></i>
                        <div>
                            <span class="fw-bold">{{ $opt['label'] }}</span>
                            <div class="fs-sm text-muted">{{ $opt['desc'] }}</div>
                        </div>
                    </div>
                    <input type="radio" class="form-check-input cursor-pointer ms-auto" name="main-theme"
                           value="{{ $val }}" {{ $val === $rsColorMode ? 'checked' : '' }}>
                </div>
            </label>
            @endforeach
        </div>

        {{-- ── Direction (live session toggle) ────────────────────────── --}}
        <div class="fw-semibold mb-2">Direction <span class="text-muted fw-normal fs-sm">(session override)</span></div>
        <div class="list-group mb-3">
            <label class="list-group-item list-group-item-action form-check border-width-1 rounded mb-0">
                <div class="d-flex flex-fill my-1">
                    <div class="form-check-label d-flex me-2">
                        <i class="ph-translate ph-lg me-3"></i>
                        <div>
                            <span class="fw-bold">RTL direction</span>
                            <div class="text-muted">Toggle between LTR and RTL</div>
                        </div>
                    </div>
                    <input type="checkbox" name="layout-direction" value="rtl"
                           class="form-check-input cursor-pointer m-0 ms-auto"
                           {{ $rsDirection === 'rtl' ? 'checked' : '' }}>
                </div>
            </label>
        </div>

        {{-- ── Persistent Settings ──────────────────────────────────────── --}}
        <div class="fw-semibold mb-2">Persistent settings</div>
        <div class="alert alert-info border-0 py-2 mb-2 fs-sm">
            <i class="ph-info me-1"></i>
            Changes below are saved to the database and apply to all users.
        </div>
        @if (Route::has('admin.settings.special.theme'))
        @can('editSpecial', \Modules\Settings\Models\Setting::class)
        <a href="{{ route('admin.settings.special.theme') }}" class="btn btn-primary btn-sm w-100 mb-1">
            <i class="ph-paint-brush me-1"></i>Open Theme Settings
        </a>
        @endcan
        @endif

    </div>
</div>
<!-- /demo config -->
