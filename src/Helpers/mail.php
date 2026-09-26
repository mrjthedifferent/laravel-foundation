<?php

use Illuminate\Support\Str;

if (! function_exists('mailThemeColors')) {
    /**
     * Resolve the accent colors used across branded emails from the active
     * dashboard theme palette (config('settings.theme_color_palette.value')).
     *
     * Layout/typography lives in the inlined CSS theme; these values are applied
     * via inline style attributes in the mail Blade components so the emails
     * always match the dashboard's currently selected color.
     *
     * @return array{primary: string, light: string, dark: string}
     */
    function mailThemeColors(): array
    {
        $palettes = [
            'blue' => ['primary' => '#0c83ff', 'light' => '#e8f3ff', 'dark' => '#0a6cd1'],
            'slate' => ['primary' => '#247297', 'light' => '#e8f4f8', 'dark' => '#1d5d7c'],
            'indigo' => ['primary' => '#5C6BC0', 'light' => '#eef0fa', 'dark' => '#4a57a4'],
            'purple' => ['primary' => '#8e70c1', 'light' => '#f3eefb', 'dark' => '#785aa8'],
            'pink' => ['primary' => '#f35c86', 'light' => '#feeef3', 'dark' => '#e23f6d'],
            'red' => ['primary' => '#EF4444', 'light' => '#fef2f2', 'dark' => '#d32f2f'],
            'orange' => ['primary' => '#f58646', 'light' => '#fef4ee', 'dark' => '#e06c2c'],
            'yellow' => ['primary' => '#d4a800', 'light' => '#fffbeb', 'dark' => '#b08c00'],
            'green' => ['primary' => '#059669', 'light' => '#ecfdf5', 'dark' => '#047857'],
            'teal' => ['primary' => '#26A69A', 'light' => '#e8f7f6', 'dark' => '#1d8b80'],
            'cyan' => ['primary' => '#049aad', 'light' => '#e8f7f9', 'dark' => '#037d8d'],
        ];

        $palette = config('settings.theme_color_palette.value', 'blue');

        return $palettes[$palette] ?? $palettes['blue'];
    }
}

if (! function_exists('mailAppName')) {
    /**
     * The application name shown in branded emails.
     */
    function mailAppName(): string
    {
        return appName();
    }
}

if (! function_exists('mailLogoUrl')) {
    /**
     * Absolute URL of the configured application logo for use in emails, or
     * null when no real logo is set.
     *
     * The `app_logo` setting accessor falls back to `images/default.png` when
     * unset, so that placeholder is treated as "no logo" — callers should then
     * render the app-name wordmark instead of a broken/placeholder image.
     */
    function mailLogoUrl(): ?string
    {
        $logo = config('settings.app_logo.value');

        if (! $logo || str_contains($logo, 'images/default.png')) {
            return null;
        }

        return Str::startsWith($logo, ['http://', 'https://'])
            ? $logo
            : url($logo);
    }
}
