@php
    $theme = $theme ?? [];
    $rsColorMode   = $theme['colorMode'] ?? config('settings.theme_color_mode.value', 'light');
    $rsDirection   = $theme['direction'] ?? config('settings.theme_direction.value', 'ltr');
    $rsPalette     = $theme['palette'] ?? config('settings.theme_color_palette.value', 'indigo');
    $rsSidebarClr  = $theme['sidebarColor'] ?? config('settings.theme_sidebar_color.value', 'light');
    $rsSidebarType = $theme['sidebarType'] ?? config('settings.theme_sidebar_type.value', 'default');
@endphp
<!-- Appearance -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="demo_config" aria-labelledby="demo_config_title">

    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="demo_config_title">{{ __('foundation::foundation.theme_config.title') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
            aria-label="{{ __('foundation::foundation.common.close') }}"></button>
    </div>

    <div class="offcanvas-body">

        {{-- What this browser is showing right now --}}
        <div class="d-flex flex-wrap gap-1 mb-4">
            <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst($rsColorMode) }}</span>
            <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ strtoupper($rsDirection) }}</span>
            <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst($rsPalette) }}</span>
            <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ __('foundation::foundation.theme_config.sidebar_badge', ['color' => ucfirst($rsSidebarClr), 'type' => ucfirst($rsSidebarType)]) }}</span>
        </div>

        {{-- Colour mode: this browser only, until the setting is saved --}}
        <div class="fd-overline mb-2">{{ __('foundation::foundation.theme_config.color_mode') }}</div>
        <p class="fs-sm text-muted">{{ __('foundation::foundation.theme_config.session_override') }}</p>
        <div class="list-group mb-4">
            @foreach(['light' => ['icon'=>'ph-sun','label'=>__('foundation::foundation.theme_config.light_theme'),'desc'=>__('foundation::foundation.theme_config.light_theme_desc')],
                       'dark'  => ['icon'=>'ph-moon','label'=>__('foundation::foundation.theme_config.dark_theme'),'desc'=>__('foundation::foundation.theme_config.dark_theme_desc')],
                       'auto'  => ['icon'=>'ph-circle-half','label'=>__('foundation::foundation.theme_config.auto_theme'),'desc'=>__('foundation::foundation.theme_config.auto_theme_desc')]] as $val => $opt)
            <label class="list-group-item d-flex align-items-center gap-3 @if($val === $rsColorMode) rs-item-selected @endif">
                <i class="{{ $opt['icon'] }} ph-lg"></i>
                <span class="flex-fill min-width-0">
                    <span class="fw-semibold d-block text-strong">{{ $opt['label'] }}</span>
                    <span class="fs-sm text-muted">{{ $opt['desc'] }}</span>
                </span>
                <input type="radio" class="form-check-input cursor-pointer m-0" name="main-theme"
                       value="{{ $val }}" {{ $val === $rsColorMode ? 'checked' : '' }}>
            </label>
            @endforeach
        </div>

        {{-- Direction: also this browser only --}}
        <div class="fd-overline mb-2">{{ __('foundation::foundation.theme_config.direction') }}</div>
        <div class="list-group mb-4">
            <label class="list-group-item d-flex align-items-center gap-3">
                <i class="ph-text-aa ph-lg"></i>
                <span class="flex-fill min-width-0">
                    <span class="fw-semibold d-block text-strong">{{ __('foundation::foundation.theme_config.rtl_direction') }}</span>
                    <span class="fs-sm text-muted">{{ __('foundation::foundation.theme_config.rtl_direction_desc') }}</span>
                </span>
                <input type="checkbox" name="layout-direction" value="rtl"
                       class="form-check-input cursor-pointer m-0"
                       {{ $rsDirection === 'rtl' ? 'checked' : '' }}>
            </label>
        </div>

        {{-- Everything else lives in the saved theme settings --}}
        @if (Route::has('admin.settings.special.theme'))
        @can('editSpecial', \Modules\Settings\Models\Setting::class)
        <p class="fs-sm text-muted">{{ __('foundation::foundation.theme_config.persistent_settings_note') }}</p>
        <a href="{{ route('admin.settings.special.theme') }}" class="btn btn-light w-100">
            <i class="ph-paint-brush"></i>{{ __('foundation::foundation.theme_config.open_theme_settings') }}
        </a>
        @endcan
        @endif

    </div>
</div>
<!-- /appearance -->
