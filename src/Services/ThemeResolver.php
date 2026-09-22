<?php

namespace Mrj\Foundation\Services;

/** @internal */
final readonly class ThemeResolver
{
    private const string HEX_PATTERN = '/^#[0-9a-fA-F]{6}$/';

    /** The curated palettes, plus 'custom', which reads the brand colour below. */
    private const array PALETTES = [
        'indigo', 'blue', 'violet', 'teal', 'green', 'amber', 'rose', 'slate', 'custom',
    ];

    /** Palette names earlier versions saved; the stylesheet maps them to the nearest curated one. */
    private const array PALETTE_ALIASES = [
        'purple' => 'violet',
        'cyan' => 'teal',
        'orange' => 'amber',
        'yellow' => 'amber',
        'pink' => 'rose',
        'red' => 'rose',
    ];

    public function resolve(): array
    {
        $colorMode = $this->sanitizeOne(config('settings.theme_color_mode.value', 'light'), ['light', 'dark', 'auto'], 'light');
        $direction = $this->sanitizeOne(config('settings.theme_direction.value', 'ltr'), ['ltr', 'rtl'], 'ltr');
        $palette = $this->sanitizePalette(config('settings.theme_color_palette.value', 'indigo'));
        $sidebarColor = $this->sanitizeOne(config('settings.theme_sidebar_color.value', 'light'), ['light', 'dark'], 'light');
        $sidebarType = $this->sanitizeOne(config('settings.theme_sidebar_type.value', 'default'), ['default', 'mini'], 'default');
        $customColor = $this->sanitizeHex(config('settings.theme_custom_color.value', '#4f46e5'), '#4f46e5');

        [$ccR, $ccG, $ccB] = $this->hexToRgb($customColor);

        return [
            'colorMode' => $colorMode,
            'direction' => $direction,
            'palette' => $palette,
            'sidebarColor' => $sidebarColor,
            'sidebarType' => $sidebarType,
            'customColor' => $customColor,
            'customColorDark' => $this->darkenHex($customColor, 0.88),
            'ccR' => $ccR,
            'ccG' => $ccG,
            'ccB' => $ccB,
            'cssDir' => $direction === 'rtl' ? 'rtl' : 'ltr',
            'favicon' => config('settings.favicon.value') ?: asset('favicon.ico'),
            'windowTheme' => [
                'colorMode' => $colorMode,
                'direction' => $direction,
                'palette' => $palette,
                'sidebarColor' => $sidebarColor,
                'sidebarType' => $sidebarType,
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

    /**
     * A value saved for an option that no longer exists falls back to the default.
     *
     * @param  list<string>  $allowed
     */
    private function sanitizeOne(mixed $value, array $allowed, string $default): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }

    private function sanitizePalette(mixed $value): string
    {
        if (! is_string($value)) {
            return 'indigo';
        }

        $value = self::PALETTE_ALIASES[$value] ?? $value;

        return in_array($value, self::PALETTES, true) ? $value : 'indigo';
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
