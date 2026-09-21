<?php

namespace Mrj\Foundation\Services;

final readonly class ThemeResolver
{
    private const string HEX_PATTERN = '/^#[0-9a-fA-F]{6}$/';

    private const array FONT_STACKS = [
        'inter' => "'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif",
        'roboto' => "'Roboto', system-ui, -apple-system, 'Segoe UI', sans-serif",
        'poppins' => "'Poppins', system-ui, -apple-system, 'Segoe UI', sans-serif",
        'system' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
    ];

    private const array GOOGLE_FONT_URLS = [
        'roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap',
        'poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
    ];

    public function resolve(): array
    {
        $layout = $this->sanitizeLayout(config('settings.theme_layout.value', '1'));
        $colorMode = config('settings.theme_color_mode.value', 'light');
        $direction = config('settings.theme_direction.value', 'ltr');
        $palette = config('settings.theme_color_palette.value', 'blue');
        $fontFamily = config('settings.theme_font_family.value', 'inter');
        $sidebarColor = config('settings.theme_sidebar_color.value', 'dark');
        $sidebarColorCustom = $this->sanitizeHex(config('settings.theme_sidebar_color_custom.value', ''));
        $sidebarType = config('settings.theme_sidebar_type.value', 'default');
        $navbarColor = config('settings.theme_navbar_color.value', 'dark');
        $navbarBg = $this->sanitizeHex(config('settings.theme_navbar_bg.value', ''));
        $customColor = $this->sanitizeHex(config('settings.theme_custom_color.value', '#0c83ff'), '#0c83ff');

        [$ccR, $ccG, $ccB] = $this->hexToRgb($customColor);
        $customColorDark = $this->darkenHex($customColor, 0.88);

        $fontStack = self::FONT_STACKS[$fontFamily] ?? self::FONT_STACKS['inter'];
        $googleFontUrl = self::GOOGLE_FONT_URLS[$fontFamily] ?? null;
        $cssDir = $direction === 'rtl' ? 'rtl' : 'ltr';
        $favicon = config('settings.favicon.value') ?: asset('favicon.ico');

        return [
            'layout' => $layout,
            'colorMode' => $colorMode,
            'direction' => $direction,
            'palette' => $palette,
            'fontFamily' => $fontFamily,
            'fontStack' => $fontStack,
            'googleFontUrl' => $googleFontUrl,
            'sidebarColor' => $sidebarColor,
            'sidebarColorCustom' => $sidebarColorCustom,
            'sidebarType' => $sidebarType,
            'navbarColor' => $navbarColor,
            'navbarBg' => $navbarBg,
            'customColor' => $customColor,
            'customColorDark' => $customColorDark,
            'ccR' => $ccR,
            'ccG' => $ccG,
            'ccB' => $ccB,
            'cssDir' => $cssDir,
            'favicon' => $favicon,
            'isLayoutStatic' => $layout === '3',
            'windowTheme' => [
                'colorMode' => $colorMode,
                'direction' => $direction,
                'palette' => $palette,
                'layout' => $layout,
                'sidebarColor' => $sidebarColor,
                'sidebarColorCustom' => $sidebarColorCustom,
                'sidebarType' => $sidebarType,
                'navbarColor' => $navbarColor,
                'navbarBg' => $navbarBg,
                'fontFamily' => $fontFamily,
                'customColor' => $customColor,
            ],
        ];
    }

    public function resolveForGuest(): array
    {
        $theme = $this->resolve();

        return [
            ...$theme,
            'windowTheme' => [
                'colorMode' => $theme['colorMode'],
                'direction' => $theme['direction'],
                'palette' => $theme['palette'],
                'fontFamily' => $theme['fontFamily'],
                'navbarColor' => $theme['navbarColor'],
                'customColor' => $theme['customColor'],
            ],
        ];
    }

    private function sanitizeHex(?string $value, string $default = ''): string
    {
        if ($value === null || $value === '' || ! preg_match(self::HEX_PATTERN, $value)) {
            return $default;
        }

        return $value;
    }

    private function sanitizeLayout(string $value): string
    {
        return in_array($value, ['1', '2', '3'], true) ? $value : '1';
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hexToRgb(string $hex): array
    {
        return [
            (int) hexdec(substr($hex, 1, 2)),
            (int) hexdec(substr($hex, 3, 2)),
            (int) hexdec(substr($hex, 5, 2)),
        ];
    }

    private function darkenHex(string $hex, float $factor): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $dr = max(0, (int) round($r * $factor));
        $dg = max(0, (int) round($g * $factor));
        $db = max(0, (int) round($b * $factor));

        return sprintf('#%02x%02x%02x', $dr, $dg, $db);
    }
}
