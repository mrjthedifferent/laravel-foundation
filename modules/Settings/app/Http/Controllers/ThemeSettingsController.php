<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Settings\Models\Setting;
use Modules\Settings\Providers\SettingsServiceProvider;
use Mrj\Foundation\Http\Controllers\Controller;

class ThemeSettingsController extends Controller
{
    /**
     * All theme setting keys managed by this controller.
     */
    private const array THEME_KEYS = [
        'theme_color_mode',
        'theme_direction',
        'theme_color_palette',
        'theme_custom_color',
        'theme_sidebar_color',
        'theme_sidebar_type',
    ];

    /** Palette names earlier versions saved, mapped to the curated palette that replaced them. */
    private const array PALETTE_ALIASES = [
        'purple' => 'violet',
        'cyan' => 'teal',
        'orange' => 'amber',
        'yellow' => 'amber',
        'pink' => 'rose',
        'red' => 'rose',
    ];

    /** What each key accepts; a value outside the list falls back to the default. */
    private const array THEME_CHOICES = [
        'theme_color_mode' => ['light', 'dark', 'auto'],
        'theme_direction' => ['ltr', 'rtl'],
        'theme_color_palette' => ['indigo', 'blue', 'violet', 'teal', 'green', 'amber', 'rose', 'slate', 'custom'],
        'theme_sidebar_color' => ['light', 'dark'],
        'theme_sidebar_type' => ['default', 'mini'],
    ];

    /**
     * Display the theme settings form.
     */
    public function show(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);

        $defaults = $this->defaults();

        $theme = [];
        foreach (self::THEME_KEYS as $key) {
            $theme[$key] = $this->normalize($key, config("settings.{$key}.value") ?? $defaults[$key] ?? '');
        }

        return view('settings::special.theme', compact('theme'));
    }

    /**
     * Update the theme settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        $defaults = $this->defaults();

        foreach (self::THEME_KEYS as $key) {
            $value = $this->normalize($key, $request->input($key, $defaults[$key] ?? ''));

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'Theme',
                    'type' => $this->keyType($key),
                    'is_visible' => false,
                ]
            );
        }

        Cache::forget(SettingsServiceProvider::cacheKey());

        return redirect()->back()->with('success', __('settings::settings.flash.theme_updated'));
    }

    /**
     * Default values for each theme key.
     *
     * @return array<string, string>
     */
    private function defaults(): array
    {
        return [
            'theme_color_mode' => 'light',
            'theme_direction' => 'ltr',
            'theme_color_palette' => 'indigo',
            'theme_custom_color' => '#4f46e5',
            'theme_sidebar_color' => 'light',
            'theme_sidebar_type' => 'default',
        ];
    }

    /**
     * Bring a stored or submitted value into range: a palette an earlier version saved maps to
     * the nearest curated one, and anything else unknown falls back to the default.
     */
    private function normalize(string $key, mixed $value): string
    {
        $default = $this->defaults()[$key] ?? '';

        if (! is_string($value)) {
            return $default;
        }

        if ($key === 'theme_custom_color') {
            return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $default;
        }

        if ($key === 'theme_color_palette') {
            $value = self::PALETTE_ALIASES[$value] ?? $value;
        }

        if (isset(self::THEME_CHOICES[$key]) && ! in_array($value, self::THEME_CHOICES[$key], true)) {
            return $default;
        }

        return $value;
    }

    /**
     * Setting type for each key.
     */
    private function keyType(string $key): string
    {
        return $key === 'theme_custom_color' ? 'text' : 'select';
    }
}
