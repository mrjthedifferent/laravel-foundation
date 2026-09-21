@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">Theme Settings</span>
@endsection

@section('content')
    @php
        $palettes = [
            'blue' => ['label' => 'Blue', 'hex' => '#0c83ff', 'semantic' => 'Primary'],
            'slate' => ['label' => 'Slate', 'hex' => '#247297', 'semantic' => 'Secondary'],
            'indigo' => ['label' => 'Indigo', 'hex' => '#5C6BC0', 'semantic' => ''],
            'purple' => ['label' => 'Purple', 'hex' => '#8e70c1', 'semantic' => ''],
            'pink' => ['label' => 'Pink', 'hex' => '#f35c86', 'semantic' => ''],
            'red' => ['label' => 'Red', 'hex' => '#EF4444', 'semantic' => 'Danger'],
            'orange' => ['label' => 'Orange', 'hex' => '#f58646', 'semantic' => 'Warning'],
            'yellow' => ['label' => 'Yellow', 'hex' => '#ffd648', 'semantic' => ''],
            'green' => ['label' => 'Green', 'hex' => '#059669', 'semantic' => 'Success'],
            'teal' => ['label' => 'Teal', 'hex' => '#26A69A', 'semantic' => ''],
            'cyan' => ['label' => 'Cyan', 'hex' => '#049aad', 'semantic' => 'Info'],
        ];

        $layouts = [
            '1' => [
                'label' => 'Layout 1',
                'description' => 'Dark navbar on top. Sidebar inside page-content.',
                'icon' => 'ph-layout',
            ],
            '2' => [
                'label' => 'Layout 2',
                'description' => 'Sidebar first with logo header. Navbar inside content-wrapper.',
                'icon' => 'ph-sidebar',
            ],
            '3' => [
                'label' => 'Layout 3',
                'description' => 'Detached sidebar. Static document scroll.',
                'icon' => 'ph-columns',
            ],
        ];

        $currentPalette = $theme['theme_color_palette'] ?? 'blue';
        $currentCustomColor = $theme['theme_custom_color'] ?? '#0c83ff';
        $currentMode = $theme['theme_color_mode'] ?? 'light';
        $currentLayout = $theme['theme_layout'] ?? '1';
        $currentDir = $theme['theme_direction'] ?? 'ltr';
        $currentSidebarC = $theme['theme_sidebar_color'] ?? 'dark';
        $currentSidebarColorCustom = $theme['theme_sidebar_color_custom'] ?? '';
        $currentSidebarT = $theme['theme_sidebar_type'] ?? 'default';
        $currentNavbarC = $theme['theme_navbar_color'] ?? 'dark';
        $currentNavbarBg = $theme['theme_navbar_bg'] ?? '';
        $currentFont = $theme['theme_font_family'] ?? 'inter';
    @endphp

    <form action="{{ route('admin.settings.special.update_theme') }}" method="POST" id="theme-form">
        @csrf

        {{-- ── Layout ──────────────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header py-2 d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:32px;height:32px;">
                    <i class="ph-layout"></i>
                </div>
                <div>
                    <div class="fw-bold">Layout</div>
                    <div class="text-muted fs-xs">Choose the overall page structure</div>
                </div>
                <a href="{{ route('admin.dashboard') }}" target="_blank" class="ms-auto btn btn-sm btn-outline-secondary">
                    <i class="ph-arrow-square-out me-1"></i>Preview
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($layouts as $value => $layout)
                        <div class="col-sm-6 col-xl-4">
                            <label class="d-block cursor-pointer">
                                <input type="radio" name="theme_layout" value="{{ $value }}"
                                    class="d-none theme-card-radio"
                                    {{ $currentLayout === (string) $value ? 'checked' : '' }}>
                                <div
                                    class="border rounded p-3 h-100 theme-radio-card {{ $currentLayout === (string) $value ? 'theme-card-selected' : '' }}">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="{{ $layout['icon'] }} fs-5 text-primary"></i>
                                        <span class="fw-semibold">{{ $layout['label'] }}</span>
                                        <i
                                            class="ph-check-circle text-primary ms-auto {{ $currentLayout === (string) $value ? '' : 'd-none' }} check-icon"></i>
                                    </div>
                                    <div class="text-muted fs-sm">{{ $layout['description'] }}</div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Color Mode ──────────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header py-2 d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:32px;height:32px;">
                    <i class="ph-moon"></i>
                </div>
                <div>
                    <div class="fw-bold">Color Mode</div>
                    <div class="text-muted fs-xs">Light, dark, or follow system preference</div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach (['light' => ['label' => 'Light', 'icon' => 'ph-sun', 'desc' => 'Clean light interface'], 'dark' => ['label' => 'Dark', 'icon' => 'ph-moon', 'desc' => 'Easy on the eyes in low light'], 'auto' => ['label' => 'Auto', 'icon' => 'ph-device-mobile', 'desc' => 'Follows system preference']] as $value => $opt)
                        <div class="col-sm-4">
                            <label class="d-block cursor-pointer">
                                <input type="radio" name="theme_color_mode" value="{{ $value }}"
                                    class="d-none theme-card-radio" data-live="colorMode"
                                    {{ $currentMode === $value ? 'checked' : '' }}>
                                <div
                                    class="border rounded p-3 text-center theme-radio-card {{ $currentMode === $value ? 'theme-card-selected' : '' }}">
                                    <i class="{{ $opt['icon'] }} fs-3 text-primary d-block mb-1"></i>
                                    <div class="fw-semibold">{{ $opt['label'] }}</div>
                                    <div class="text-muted fs-xs">{{ $opt['desc'] }}</div>
                                    <i
                                        class="ph-check-circle text-primary mt-1 d-block {{ $currentMode === $value ? '' : 'd-none' }} check-icon"></i>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Color Palette ────────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header py-2 d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:32px;height:32px;">
                    <i class="ph-palette"></i>
                </div>
                <div>
                    <div class="fw-bold">Color Palette</div>
                    <div class="text-muted fs-xs">Sets the primary accent color across the UI</div>
                </div>
            </div>
            <div class="card-body">
                {{-- Hidden field that always carries the active palette value --}}
                <input type="hidden" name="theme_color_palette" id="palette-value" value="{{ $currentPalette }}">
                {{-- Hidden field that carries the custom hex --}}
                <input type="hidden" name="theme_custom_color" id="custom-color-value" value="{{ $currentCustomColor }}">

                <div class="row g-2" id="palette-presets">
                    @foreach ($palettes as $value => $pal)
                        <div class="col-md-6 col-sm-4 col-md-3 col-xl-2">
                            <div class="border rounded overflow-hidden palette-preset-card cursor-pointer {{ $currentPalette === $value ? 'theme-card-selected' : '' }}"
                                data-palette="{{ $value }}">
                                <div class="d-flex align-items-center justify-content-between px-2 pt-2 pb-1">
                                    <div>
                                        <div class="fw-semibold fs-sm">{{ $pal['label'] }}</div>
                                        @if ($pal['semantic'])
                                            <div class="text-muted" style="font-size:10px;">{{ $pal['semantic'] }}</div>
                                        @endif
                                    </div>
                                    <i
                                        class="ph-check-circle text-primary check-icon {{ $currentPalette === $value ? '' : 'd-none' }}"></i>
                                </div>
                                <div style="height:8px;background-color:{{ $pal['hex'] }};"></div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Custom colour picker card --}}
                    <div class="col-md-6 col-sm-4 col-md-3 col-xl-2">
                        <div class="border rounded overflow-hidden palette-preset-card cursor-pointer {{ $currentPalette === 'custom' ? 'theme-card-selected' : '' }}"
                            data-palette="custom">
                            <div class="d-flex align-items-center justify-content-between px-2 pt-2 pb-1">
                                <div>
                                    <div class="fw-semibold fs-sm">Custom</div>
                                    <div class="text-muted" style="font-size:10px;">Pick any color</div>
                                </div>
                                <i
                                    class="ph-check-circle text-primary check-icon {{ $currentPalette === 'custom' ? '' : 'd-none' }}"></i>
                            </div>
                            <div id="custom-palette-swatch" style="height:8px;background-color:{{ $currentCustomColor }};">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Custom colour input — shown only when "Custom" is active --}}
                <div id="custom-color-picker-wrap"
                    class="mt-3 p-3 border rounded bg-body-tertiary {{ $currentPalette === 'custom' ? '' : 'd-none' }}">
                    <label class="form-label fw-semibold fs-sm mb-2">
                        <i class="ph-eyedropper me-1"></i>Custom Primary Color
                    </label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <input type="color" id="custom-color-picker" value="{{ $currentCustomColor }}"
                            class="form-control form-control-color flex-shrink-0 cursor-pointer"
                            style="width:48px;height:38px;padding:2px 4px;">
                        <input type="text" id="custom-color-hex" value="{{ $currentCustomColor }}"
                            class="form-control form-control-sm font-monospace" placeholder="#0c83ff" maxlength="7"
                            style="max-width:120px;">
                        <span class="text-muted fs-xs">Choose or type a hex color, then save.</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Direction ────────────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header py-2 d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:32px;height:32px;">
                    <i class="ph-arrows-left-right"></i>
                </div>
                <div>
                    <div class="fw-bold">Text Direction</div>
                    <div class="text-muted fs-xs">LTR or RTL layout</div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach (['ltr' => ['label' => 'LTR', 'icon' => 'ph-text-align-left', 'desc' => 'Left to Right (default)'], 'rtl' => ['label' => 'RTL', 'icon' => 'ph-text-align-right', 'desc' => 'Right to Left (Arabic, Hebrew…)']] as $value => $opt)
                        <div class="col-sm-6">
                            <label class="d-block cursor-pointer">
                                <input type="radio" name="theme_direction" value="{{ $value }}"
                                    class="d-none theme-card-radio" {{ $currentDir === $value ? 'checked' : '' }}>
                                <div
                                    class="border rounded p-3 d-flex align-items-center gap-3 theme-radio-card {{ $currentDir === $value ? 'theme-card-selected' : '' }}">
                                    <i class="{{ $opt['icon'] }} fs-4 text-primary flex-shrink-0"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $opt['label'] }}</div>
                                        <div class="text-muted fs-xs">{{ $opt['desc'] }}</div>
                                    </div>
                                    <i
                                        class="ph-check-circle text-primary {{ $currentDir === $value ? '' : 'd-none' }} check-icon"></i>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header py-2 d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:32px;height:32px;">
                    <i class="ph-sidebar-simple"></i>
                </div>
                <div>
                    <div class="fw-bold">Sidebar</div>
                    <div class="text-muted fs-xs">Color scheme and display type</div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold fs-sm">Color Scheme</label>
                        <input type="hidden" name="theme_sidebar_color" id="sidebar-color-value"
                            value="{{ $currentSidebarC }}">
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach (['dark' => 'Dark', 'light' => 'Light', 'primary' => 'Primary'] as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="_sidebar_color_radio" value="{{ $value }}"
                                        class="d-none sidebar-color-radio"
                                        {{ $currentSidebarC === $value ? 'checked' : '' }}>
                                    <span
                                        class="btn btn-sm {{ $currentSidebarC === $value ? 'btn-primary' : 'btn-outline-secondary' }} sidebar-color-radio-btn">
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="form-text">Primary tints the sidebar with the active palette color.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold fs-sm">Custom Background Color
                            <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <input type="hidden" name="theme_sidebar_color_custom" id="sidebar-color-custom-value"
                            value="{{ $currentSidebarColorCustom }}">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <input type="color" id="sidebar-color-picker"
                                value="{{ !empty($currentSidebarColorCustom) ? $currentSidebarColorCustom : '#252b36' }}"
                                class="form-control form-control-color flex-shrink-0 cursor-pointer"
                                style="width:48px;height:38px;padding:2px 4px;{{ empty($currentSidebarColorCustom) ? 'opacity:0.4;' : '' }}"
                                {{ empty($currentSidebarColorCustom) ? 'disabled' : '' }}>
                            <input type="text" id="sidebar-color-hex" value="{{ $currentSidebarColorCustom }}"
                                class="form-control form-control-sm font-monospace" placeholder="#252b36" maxlength="7"
                                style="max-width:110px;{{ empty($currentSidebarColorCustom) ? 'opacity:0.4;' : '' }}"
                                {{ empty($currentSidebarColorCustom) ? 'disabled' : '' }}>
                            <label class="form-check mb-0 ms-1 cursor-pointer">
                                <input type="checkbox" class="form-check-input" id="sidebar-color-use-default"
                                    {{ empty($currentSidebarColorCustom) ? 'checked' : '' }}>
                                <span class="form-check-label fs-sm">Use default</span>
                            </label>
                        </div>
                        <div class="form-text">Leave on "Use default" to keep the Dark/Light preset colour.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold fs-sm">Display Type</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach (['default' => 'Default', 'mini' => 'Mini (Icon)'] as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="theme_sidebar_type" value="{{ $value }}"
                                        class="d-none theme-btn-radio-input"
                                        {{ $currentSidebarT === $value ? 'checked' : '' }}>
                                    <span
                                        class="btn btn-sm {{ $currentSidebarT === $value ? 'btn-primary' : 'btn-outline-secondary' }} theme-btn-radio">
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header py-2 d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:32px;height:32px;">
                    <i class="ph-rows"></i>
                </div>
                <div>
                    <div class="fw-bold">Navbar</div>
                    <div class="text-muted fs-xs">Top navigation bar color scheme and optional background override</div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold fs-sm">Color Scheme</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach (['dark' => 'Dark', 'light' => 'Light', 'primary' => 'Primary'] as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="theme_navbar_color" value="{{ $value }}"
                                        class="d-none theme-btn-radio-input"
                                        {{ $currentNavbarC === $value ? 'checked' : '' }}>
                                    <span
                                        class="btn btn-sm {{ $currentNavbarC === $value ? 'btn-primary' : 'btn-outline-secondary' }} theme-btn-radio">
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="form-text">Primary tints the navbar with the active palette color.</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold fs-sm">Custom Background Color
                            <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        {{-- Hidden field carries the actual value (hex or empty) --}}
                        <input type="hidden" name="theme_navbar_bg" id="navbar-bg-value"
                            value="{{ $currentNavbarBg }}">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            {{-- Color picker --}}
                            <input type="color" id="navbar-bg-picker"
                                value="{{ !empty($currentNavbarBg) ? $currentNavbarBg : '#32333a' }}"
                                class="form-control form-control-color flex-shrink-0 cursor-pointer"
                                style="width:48px;height:38px;padding:2px 4px;{{ empty($currentNavbarBg) ? 'opacity:0.4;' : '' }}"
                                {{ empty($currentNavbarBg) ? 'disabled' : '' }}>
                            {{-- Hex text input --}}
                            <input type="text" id="navbar-bg-hex" value="{{ $currentNavbarBg }}"
                                class="form-control form-control-sm font-monospace" placeholder="#32333a" maxlength="7"
                                style="max-width:110px;{{ empty($currentNavbarBg) ? 'opacity:0.4;' : '' }}"
                                {{ empty($currentNavbarBg) ? 'disabled' : '' }}>
                            {{-- None toggle --}}
                            <label class="form-check mb-0 ms-1 cursor-pointer">
                                <input type="checkbox" class="form-check-input" id="navbar-bg-none"
                                    {{ empty($currentNavbarBg) ? 'checked' : '' }}>
                                <span class="form-check-label fs-sm">Use default</span>
                            </label>
                        </div>
                        <div class="form-text">Leave on "Use default" to keep the theme's built-in navbar colour.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Typography ───────────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header py-2 d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:32px;height:32px;">
                    <i class="ph-text-t"></i>
                </div>
                <div>
                    <div class="fw-bold">Typography</div>
                    <div class="text-muted fs-xs">Global font family applied to the entire application</div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ([
            'inter' => ['label' => 'Inter', 'preview' => 'The quick brown fox jumps over the lazy dog.', 'stack' => "'Inter', sans-serif"],
            'roboto' => ['label' => 'Roboto', 'preview' => 'The quick brown fox jumps over the lazy dog.', 'stack' => "'Roboto', sans-serif"],
            'poppins' => ['label' => 'Poppins', 'preview' => 'The quick brown fox jumps over the lazy dog.', 'stack' => "'Poppins', sans-serif"],
            'system' => ['label' => 'System Default', 'preview' => 'The quick brown fox jumps over the lazy dog.', 'stack' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"],
        ] as $value => $font)
                        <div class="col-sm-6 col-xl-3">
                            <label class="d-block cursor-pointer">
                                <input type="radio" name="theme_font_family" value="{{ $value }}"
                                    class="d-none theme-card-radio" {{ $currentFont === $value ? 'checked' : '' }}>
                                <div
                                    class="border rounded p-3 theme-radio-card {{ $currentFont === $value ? 'theme-card-selected' : '' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="fw-semibold"
                                            style="font-family:{{ $font['stack'] }};">{{ $font['label'] }}</span>
                                        <i
                                            class="ph-check-circle text-primary {{ $currentFont === $value ? '' : 'd-none' }} check-icon"></i>
                                    </div>
                                    <div class="text-muted fs-sm" style="font-family:{{ $font['stack'] }};">
                                        {{ $font['preview'] }}</div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Save ─────────────────────────────────────────────────────────── --}}
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.dashboard') }}" target="_blank" class="btn btn-outline-secondary">
                <i class="ph-arrow-square-out me-1"></i>Preview Dashboard
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="ph-floppy-disk me-1"></i>Save Theme Settings
            </button>
        </div>
    </form>
@endsection


@push('scripts')
    <script>
        (function() {

            // ── Palette preset data (mirrors PHP array) ───────────────────────────
            var paletteHex = {
                blue: '#0c83ff',
                slate: '#247297',
                indigo: '#5C6BC0',
                purple: '#8e70c1',
                pink: '#f35c86',
                red: '#EF4444',
                orange: '#f58646',
                yellow: '#ffd648',
                green: '#059669',
                teal: '#26A69A',
                cyan: '#049aad',
            };

            // ── Helpers ───────────────────────────────────────────────────────────

            /**
             * Parse "#rrggbb" → "r, g, b" string for CSS rgb values.
             */
            function hexToRgbStr(hex) {
                var r = parseInt(hex.slice(1, 3), 16),
                    g = parseInt(hex.slice(3, 5), 16),
                    b = parseInt(hex.slice(5, 7), 16);
                return r + ', ' + g + ', ' + b;
            }

            /**
             * Darken a hex colour by ~12 % (for hover states).
             */
            function darkenHex(hex) {
                var r = Math.max(0, Math.round(parseInt(hex.slice(1, 3), 16) * 0.88)),
                    g = Math.max(0, Math.round(parseInt(hex.slice(3, 5), 16) * 0.88)),
                    b = Math.max(0, Math.round(parseInt(hex.slice(5, 7), 16) * 0.88));
                return '#' + [r, g, b].map(function(v) {
                    return v.toString(16).padStart(2, '0');
                }).join('');
            }

            /**
             * Apply a hex colour as the custom palette live.
             */
            function applyCustomColor(hex) {
                var root = document.documentElement;
                root.style.setProperty('--custom-primary', hex);
                root.style.setProperty('--custom-primary-rgb', hexToRgbStr(hex));
                root.style.setProperty('--custom-primary-dark', darkenHex(hex));
                document.getElementById('custom-palette-swatch').style.backgroundColor = hex;
            }

            /**
             * Apply a named palette or "custom" to the document attribute.
             */
            function applyPalette(name) {
                document.documentElement.setAttribute('data-color-palette', name);
            }

            /**
             * Select a palette card (by preset name or "custom").
             */
            function selectPaletteCard(name) {
                // Deselect all
                document.querySelectorAll('.palette-preset-card').forEach(function(card) {
                    card.classList.remove('theme-card-selected');
                    var icon = card.querySelector('.check-icon');
                    if (icon) icon.classList.add('d-none');
                });
                // Select target
                var target = document.querySelector('.palette-preset-card[data-palette="' + name + '"]');
                if (target) {
                    target.classList.add('theme-card-selected');
                    var icon = target.querySelector('.check-icon');
                    if (icon) icon.classList.remove('d-none');
                }
                // Show/hide custom picker
                var pickerWrap = document.getElementById('custom-color-picker-wrap');
                if (name === 'custom') {
                    pickerWrap.classList.remove('d-none');
                } else {
                    pickerWrap.classList.add('d-none');
                }
                // Update hidden field
                document.getElementById('palette-value').value = name;
                // Live preview
                applyPalette(name);
                if (name !== 'custom') {
                    if (paletteHex[name]) {
                        applyCustomColor(paletteHex[name]);
                    }
                }
                // If navbar is in primary mode, its CSS var updates automatically
                // but force a repaint by toggling the class
                var navbarEl = document.getElementById('main-navbar');
                if (navbarEl && navbarEl.classList.contains('navbar-primary')) {
                    navbarEl.classList.remove('navbar-primary');
                    requestAnimationFrame(function() {
                        navbarEl.classList.add('navbar-primary');
                    });
                }
                // Same for sidebar-primary
                var sidebarEl = document.getElementById('main-sidebar');
                if (sidebarEl && sidebarEl.classList.contains('sidebar-primary')) {
                    sidebarEl.classList.remove('sidebar-primary');
                    requestAnimationFrame(function() {
                        sidebarEl.classList.add('sidebar-primary');
                    });
                }
            }

            // ── Wire palette preset card clicks ───────────────────────────────────
            document.querySelectorAll('.palette-preset-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    selectPaletteCard(this.dataset.palette);
                });
            });

            // ── Wire custom colour picker ─────────────────────────────────────────
            var colorPicker = document.getElementById('custom-color-picker');
            var colorHexInput = document.getElementById('custom-color-hex');

            function onCustomColorChange(hex) {
                // Validate
                if (!/^#[0-9a-fA-F]{6}$/.test(hex)) return;
                colorPicker.value = hex;
                colorHexInput.value = hex;
                document.getElementById('custom-color-value').value = hex;
                applyCustomColor(hex);
                // Ensure "custom" palette is selected
                selectPaletteCard('custom');
                // Refresh navbar if in primary mode
                var navbarEl = document.getElementById('main-navbar');
                if (navbarEl && navbarEl.classList.contains('navbar-primary')) {
                    navbarEl.classList.remove('navbar-primary');
                    requestAnimationFrame(function() {
                        navbarEl.classList.add('navbar-primary');
                    });
                }
                // Refresh sidebar if in primary mode
                var sidebarEl = document.getElementById('main-sidebar');
                if (sidebarEl && sidebarEl.classList.contains('sidebar-primary')) {
                    sidebarEl.classList.remove('sidebar-primary');
                    requestAnimationFrame(function() {
                        sidebarEl.classList.add('sidebar-primary');
                    });
                }
            }

            colorPicker.addEventListener('input', function() {
                onCustomColorChange(this.value);
            });

            colorHexInput.addEventListener('input', function() {
                var val = this.value.trim();
                if (/^#?[0-9a-fA-F]{6}$/.test(val)) {
                    onCustomColorChange(val.startsWith('#') ? val : '#' + val);
                }
            });

            // ── Sidebar color picker ──────────────────────────────────────────────
            (function() {
                var sidebarColorPicker = document.getElementById('sidebar-color-picker');
                var sidebarColorHex = document.getElementById('sidebar-color-hex');
                var sidebarColorValue = document.getElementById('sidebar-color-value');
                var sidebarColorCustomV = document.getElementById('sidebar-color-custom-value');
                var sidebarUseDefault = document.getElementById('sidebar-color-use-default');
                var sidebarEl = document.getElementById('main-sidebar');

                if (!sidebarColorPicker) return;

                function applySidebarBg(hex) {
                    if (!sidebarEl) return;
                    if (hex) {
                        sidebarEl.style.setProperty('background-color', hex, 'important');
                        document.documentElement.style.setProperty('--custom-sidebar-bg', hex);
                        sidebarEl.classList.remove('sidebar-dark', 'sidebar-light');
                        sidebarEl.classList.add('sidebar-custom');
                    } else {
                        sidebarEl.style.removeProperty('background-color');
                        sidebarEl.classList.remove('sidebar-custom');
                        var preset = sidebarColorValue.value || 'dark';
                        sidebarEl.classList.remove('sidebar-dark', 'sidebar-light');
                        sidebarEl.classList.add(preset === 'light' ? 'sidebar-light' : 'sidebar-dark');
                    }
                }

                function setSidebarBgEnabled(enabled) {
                    sidebarColorPicker.disabled = !enabled;
                    sidebarColorHex.disabled = !enabled;
                    sidebarColorPicker.style.opacity = enabled ? '1' : '0.4';
                    sidebarColorHex.style.opacity = enabled ? '1' : '0.4';
                    if (!enabled) {
                        sidebarColorCustomV.value = '';
                        applySidebarBg('');
                    } else {
                        var hex = sidebarColorHex.value.trim() || sidebarColorPicker.value;
                        sidebarColorCustomV.value = hex;
                        applySidebarBg(hex);
                    }
                }

                function onSidebarBgChange(hex) {
                    if (!/^#[0-9a-fA-F]{6}$/.test(hex)) return;
                    sidebarColorPicker.value = hex;
                    sidebarColorHex.value = hex;
                    sidebarColorCustomV.value = hex;
                    sidebarUseDefault.checked = false;
                    setSidebarBgEnabled(true);
                    applySidebarBg(hex);
                }

                // "Use default" checkbox — mirrors navbar exactly
                sidebarUseDefault.addEventListener('change', function() {
                    setSidebarBgEnabled(!this.checked);
                });

                sidebarColorPicker.addEventListener('input', function() {
                    onSidebarBgChange(this.value);
                });

                sidebarColorHex.addEventListener('input', function() {
                    var val = this.value.trim();
                    if (/^#?[0-9a-fA-F]{6}$/.test(val)) {
                        onSidebarBgChange(val.startsWith('#') ? val : '#' + val);
                    }
                });
            })();

            // ── Sidebar color radio (Dark / Light / Primary) ──────────────────────
            document.querySelectorAll('.sidebar-color-radio').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    // Update hidden field
                    document.getElementById('sidebar-color-value').value = this.value;
                    // Update button styles
                    document.querySelectorAll('.sidebar-color-radio').forEach(function(r) {
                        var btn = r.closest('label').querySelector('.sidebar-color-radio-btn');
                        if (btn) {
                            btn.classList.remove('btn-primary');
                            btn.classList.add('btn-outline-secondary');
                        }
                    });
                    var activeBtn = this.closest('label').querySelector('.sidebar-color-radio-btn');
                    if (activeBtn) {
                        activeBtn.classList.remove('btn-outline-secondary');
                        activeBtn.classList.add('btn-primary');
                    }
                    // Live preview — only apply if not using custom color
                    var sidebarEl = document.getElementById('main-sidebar');
                    var customV = document.getElementById('sidebar-color-custom-value');
                    if (sidebarEl && (!customV || customV.value === '')) {
                        sidebarEl.classList.remove('sidebar-dark', 'sidebar-light', 'sidebar-primary',
                            'sidebar-custom');
                        if (this.value === 'light') {
                            sidebarEl.classList.add('sidebar-light');
                        } else if (this.value === 'primary') {
                            sidebarEl.classList.add('sidebar-primary');
                        } else {
                            sidebarEl.classList.add('sidebar-dark');
                        }
                    }
                });
            });

            // ── Navbar background color picker ───────────────────────────────────
            var navbarBgPicker = document.getElementById('navbar-bg-picker');
            var navbarBgHex = document.getElementById('navbar-bg-hex');
            var navbarBgNone = document.getElementById('navbar-bg-none');
            var navbarBgValue = document.getElementById('navbar-bg-value');
            var navbarEl = document.getElementById('main-navbar');

            function applyNavbarBg(hex) {
                if (navbarEl) {
                    navbarEl.style.backgroundColor = hex || '';
                }
            }

            function setNavbarBgEnabled(enabled) {
                navbarBgPicker.disabled = !enabled;
                navbarBgHex.disabled = !enabled;
                navbarBgPicker.style.opacity = enabled ? '1' : '0.4';
                navbarBgHex.style.opacity = enabled ? '1' : '0.4';
                if (!enabled) {
                    navbarBgValue.value = '';
                    applyNavbarBg('');
                } else {
                    var hex = navbarBgHex.value.trim() || navbarBgPicker.value;
                    navbarBgValue.value = hex;
                    applyNavbarBg(hex);
                }
            }

            function onNavbarBgChange(hex) {
                if (!/^#[0-9a-fA-F]{6}$/.test(hex)) return;
                navbarBgPicker.value = hex;
                navbarBgHex.value = hex;
                navbarBgValue.value = hex;
                navbarBgNone.checked = false;
                setNavbarBgEnabled(true);
                applyNavbarBg(hex);
            }

            navbarBgNone.addEventListener('change', function() {
                setNavbarBgEnabled(!this.checked);
            });

            navbarBgPicker.addEventListener('input', function() {
                onNavbarBgChange(this.value);
            });

            navbarBgHex.addEventListener('input', function() {
                var val = this.value.trim();
                if (/^#?[0-9a-fA-F]{6}$/.test(val)) {
                    onNavbarBgChange(val.startsWith('#') ? val : '#' + val);
                }
            });

            // ── Card-style radios (layout, color mode, direction, font) ──────────
            document.querySelectorAll('.theme-card-radio').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    var name = this.name;

                    // Reset all siblings in same group
                    document.querySelectorAll('input[name="' + name + '"]').forEach(function(r) {
                        var card = r.closest('label').querySelector('.theme-radio-card');
                        var icon = r.closest('label').querySelector('.check-icon');
                        if (card) card.classList.remove('theme-card-selected');
                        if (icon) icon.classList.add('d-none');
                    });

                    // Activate selected
                    var selCard = this.closest('label').querySelector('.theme-radio-card');
                    var selIcon = this.closest('label').querySelector('.check-icon');
                    if (selCard) selCard.classList.add('theme-card-selected');
                    if (selIcon) selIcon.classList.remove('d-none');

                    // Live preview for color mode
                    if (name === 'theme_color_mode') {
                        var val = this.value;
                        if (val === 'light') {
                            document.documentElement.removeAttribute('data-color-theme');
                        } else {
                            document.documentElement.setAttribute('data-color-theme', val);
                        }
                    }
                });
            });

            // ── Button-style radios (sidebar type, navbar color) ─────────────────
            document.querySelectorAll('.theme-btn-radio-input').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    var name = this.name;
                    document.querySelectorAll('input[name="' + name + '"]').forEach(function(r) {
                        var span = r.closest('label').querySelector('.theme-btn-radio');
                        if (span) {
                            span.classList.remove('btn-primary');
                            span.classList.add('btn-outline-secondary');
                        }
                    });
                    var activeSpan = this.closest('label').querySelector('.theme-btn-radio');
                    if (activeSpan) {
                        activeSpan.classList.remove('btn-outline-secondary');
                        activeSpan.classList.add('btn-primary');
                    }

                    // ── Live preview: navbar color scheme ────────────────────────
                    if (name === 'theme_navbar_color') {
                        var navbarEl = document.getElementById('main-navbar');
                        if (navbarEl) {
                            navbarEl.classList.remove('navbar-dark', 'navbar-light', 'navbar-primary');
                            if (this.value === 'light') {
                                navbarEl.classList.add('navbar-light');
                            } else if (this.value === 'primary') {
                                navbarEl.classList.add('navbar-primary');
                            } else {
                                navbarEl.classList.add('navbar-dark');
                            }
                        }
                        // Update search bar color-theme so icons/text stay visible
                        var searchBar = navbarEl ? navbarEl.querySelector(
                            '.form-control-feedback-start') : null;
                        if (searchBar) {
                            searchBar.setAttribute('data-color-theme', this.value === 'light' ?
                                'light' : 'dark');
                        }
                    }
                });
            });

        })();
    </script>
@endpush
