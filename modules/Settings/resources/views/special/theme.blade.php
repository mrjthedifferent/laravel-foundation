@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('settings::settings.special_theme.breadcrumb') }}</span>
@endsection

@section('content')
    <x-page-header :title="__('settings::settings.special_theme.title')"
        :subtitle="__('settings::settings.special_theme.subtitle')" icon="ph-paint-brush" />

    @php
        $palettes = [
            'indigo' => __('settings::settings.special_theme.palette_indigo'),
            'blue' => __('settings::settings.special_theme.palette_blue'),
            'violet' => __('settings::settings.special_theme.palette_violet'),
            'teal' => __('settings::settings.special_theme.palette_teal'),
            'green' => __('settings::settings.special_theme.palette_green'),
            'amber' => __('settings::settings.special_theme.palette_amber'),
            'rose' => __('settings::settings.special_theme.palette_rose'),
            'slate' => __('settings::settings.special_theme.palette_slate'),
        ];
        $customColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($theme['theme_custom_color'] ?? ''))
            ? $theme['theme_custom_color']
            : '#4f46e5';
    @endphp

    <form method="POST" action="{{ route('admin.settings.special.update_theme') }}" id="theme-form">
        @csrf

        <div class="row g-3">
            <div class="col-xl-7">

                {{-- Colour mode --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('settings::settings.special_theme.color_mode_header') }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted fs-sm mb-3">{{ __('settings::settings.special_theme.color_mode_subtitle') }}</p>
                        <div class="row g-2">
                            @foreach ([
                                'light' => ['icon' => 'ph-sun', 'label' => __('settings::settings.special_theme.mode_light'), 'desc' => __('settings::settings.special_theme.mode_light_desc')],
                                'dark' => ['icon' => 'ph-moon', 'label' => __('settings::settings.special_theme.mode_dark'), 'desc' => __('settings::settings.special_theme.mode_dark_desc')],
                                'auto' => ['icon' => 'ph-circle-half', 'label' => __('settings::settings.special_theme.mode_auto'), 'desc' => __('settings::settings.special_theme.mode_auto_desc')],
                            ] as $value => $mode)
                                <div class="col-sm-4">
                                    <label class="fd-choice @if (($theme['theme_color_mode'] ?? 'light') === $value) is-selected @endif">
                                        <input type="radio" name="theme_color_mode" value="{{ $value }}" class="form-check-input"
                                            @checked(($theme['theme_color_mode'] ?? 'light') === $value)>
                                        <i class="{{ $mode['icon'] }} ph-lg"></i>
                                        <span class="fw-semibold text-strong">{{ $mode['label'] }}</span>
                                        <span class="fs-xs text-muted">{{ $mode['desc'] }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Accent --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('settings::settings.special_theme.palette_header') }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted fs-sm mb-3">{{ __('settings::settings.special_theme.palette_subtitle') }}</p>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach ($palettes as $name => $paletteLabel)
                                <label class="fd-swatch @if (($theme['theme_color_palette'] ?? 'indigo') === $name) is-selected @endif">
                                    <input type="radio" name="theme_color_palette" value="{{ $name }}" class="visually-hidden"
                                        @checked(($theme['theme_color_palette'] ?? 'indigo') === $name)>
                                    <span class="fd-swatch-dot" data-swatch="{{ $name }}"></span>
                                    <span class="fs-xs">{{ $paletteLabel }}</span>
                                </label>
                            @endforeach

                            <label class="fd-swatch @if (($theme['theme_color_palette'] ?? 'indigo') === 'custom') is-selected @endif">
                                <input type="radio" name="theme_color_palette" value="custom" class="visually-hidden"
                                    @checked(($theme['theme_color_palette'] ?? 'indigo') === 'custom')>
                                <span class="fd-swatch-dot" id="custom-swatch-dot"></span>
                                <span class="fs-xs">{{ __('settings::settings.special_theme.custom_label') }}</span>
                            </label>
                        </div>

                        <label for="theme_custom_color" class="form-label">{{ __('settings::settings.special_theme.custom_primary_color_label') }}</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color" id="theme_custom_color_picker"
                                value="{{ $customColor }}" aria-label="{{ __('settings::settings.special_theme.custom_primary_color_label') }}">
                            <input type="text" class="form-control w-sm font-monospace" id="theme_custom_color"
                                name="theme_custom_color" value="{{ $customColor }}" pattern="^#[0-9a-fA-F]{6}$">
                        </div>
                        <div class="form-text">{{ __('settings::settings.special_theme.custom_color_hint') }}</div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('settings::settings.special_theme.sidebar_header') }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted fs-sm mb-3">{{ __('settings::settings.special_theme.sidebar_subtitle') }}</p>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="theme_sidebar_color" class="form-label">{{ __('settings::settings.special_theme.color_scheme_label') }}</label>
                                <select name="theme_sidebar_color" id="theme_sidebar_color" class="form-select">
                                    <option value="light" @selected(($theme['theme_sidebar_color'] ?? 'light') === 'light')>{{ __('settings::settings.special_theme.scheme_light') }}</option>
                                    <option value="dark" @selected(($theme['theme_sidebar_color'] ?? 'light') === 'dark')>{{ __('settings::settings.special_theme.scheme_dark') }}</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="theme_sidebar_type" class="form-label">{{ __('settings::settings.special_theme.display_type_label') }}</label>
                                <select name="theme_sidebar_type" id="theme_sidebar_type" class="form-select">
                                    <option value="default" @selected(($theme['theme_sidebar_type'] ?? 'default') === 'default')>{{ __('settings::settings.special_theme.display_default') }}</option>
                                    <option value="mini" @selected(($theme['theme_sidebar_type'] ?? 'default') === 'mini')>{{ __('settings::settings.special_theme.display_mini') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Direction --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('settings::settings.special_theme.direction_header') }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted fs-sm mb-3">{{ __('settings::settings.special_theme.direction_subtitle') }}</p>
                        <div class="row g-2">
                            @foreach ([
                                'ltr' => ['label' => __('settings::settings.special_theme.direction_ltr'), 'desc' => __('settings::settings.special_theme.direction_ltr_desc')],
                                'rtl' => ['label' => __('settings::settings.special_theme.direction_rtl'), 'desc' => __('settings::settings.special_theme.direction_rtl_desc')],
                            ] as $value => $dir)
                                <div class="col-sm-6">
                                    <label class="fd-choice @if (($theme['theme_direction'] ?? 'ltr') === $value) is-selected @endif">
                                        <input type="radio" name="theme_direction" value="{{ $value }}" class="form-check-input"
                                            @checked(($theme['theme_direction'] ?? 'ltr') === $value)>
                                        <span class="fw-semibold text-strong">{{ $dir['label'] }}</span>
                                        <span class="fs-xs text-muted">{{ $dir['desc'] }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="ph-check"></i>{{ __('settings::settings.special_theme.submit') }}
                </button>
            </div>

            {{-- Live preview --}}
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('settings::settings.special_theme.preview') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="fd-theme-preview" id="theme-preview" data-bs-theme="light" data-color-palette="indigo">
                            <div class="fd-theme-preview-sidebar">
                                <span class="fd-theme-preview-brand"></span>
                                <span class="fd-theme-preview-item is-active"></span>
                                <span class="fd-theme-preview-item"></span>
                                <span class="fd-theme-preview-item"></span>
                            </div>
                            <div class="fd-theme-preview-main">
                                <div class="fd-theme-preview-bar"></div>
                                <div class="fd-theme-preview-body">
                                    <div class="fd-theme-preview-card"></div>
                                    <div class="fd-theme-preview-card"></div>
                                    <button type="button" class="btn btn-primary btn-sm" disabled>{{ __('foundation::foundation.common.save') }}</button>
                                </div>
                            </div>
                        </div>
                        <p class="form-text mt-3 mb-0">{{ __('settings::settings.special_theme.preview_hint') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                const form = document.getElementById('theme-form');
                const preview = document.getElementById('theme-preview');
                const hex = document.getElementById('theme_custom_color');
                const picker = document.getElementById('theme_custom_color_picker');
                const customDot = document.getElementById('custom-swatch-dot');
                if (!form || !preview) return;

                function value(name) {
                    const field = form.querySelector(`[name="${name}"]:checked`) || form.querySelector(`[name="${name}"]`);
                    return field ? field.value : '';
                }

                function paint() {
                    const mode = value('theme_color_mode');
                    const system = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    preview.dataset.bsTheme = mode === 'auto' ? (system ? 'dark' : 'light') : mode;
                    preview.dataset.colorPalette = value('theme_color_palette');
                    preview.dir = value('theme_direction');
                    preview.classList.toggle('is-dark-sidebar', value('theme_sidebar_color') === 'dark');
                    preview.classList.toggle('is-mini', value('theme_sidebar_type') === 'mini');
                    preview.style.setProperty('--custom-primary', hex.value);
                    if (customDot) customDot.style.setProperty('--fd-swatch', hex.value);

                    // The custom palette reads these, exactly as the real page does
                    const rgb = hex.value.match(/^#(\w{2})(\w{2})(\w{2})$/);
                    if (rgb) {
                        preview.style.setProperty('--custom-primary-rgb',
                            [rgb[1], rgb[2], rgb[3]].map((part) => parseInt(part, 16)).join(', '));
                    }

                    form.querySelectorAll('.fd-choice, .fd-swatch').forEach((option) => {
                        const input = option.querySelector('input');
                        option.classList.toggle('is-selected', !!input?.checked);
                    });
                }

                form.addEventListener('change', paint);
                form.addEventListener('input', paint);

                picker?.addEventListener('input', () => {
                    hex.value = picker.value;
                    paint();
                });
                hex?.addEventListener('input', () => {
                    if (/^#[0-9a-fA-F]{6}$/.test(hex.value)) {
                        picker.value = hex.value;
                        paint();
                    }
                });

                paint();
            })();
        </script>
    @endpush
@endsection
