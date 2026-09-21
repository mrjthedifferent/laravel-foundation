<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Http\Controllers\Controller;

class ThemeSettingsController extends Controller
{
    /**
     * All theme setting keys managed by this controller.
     */
    private const THEME_KEYS = [
        'theme_layout',
        'theme_color_mode',
        'theme_direction',
        'theme_color_palette',
        'theme_custom_color',
        'theme_sidebar_color',
        'theme_sidebar_color_custom',
        'theme_sidebar_type',
        'theme_navbar_color',
        'theme_navbar_bg',
        'theme_font_family',
    ];

    /**
     * Display the theme settings form.
     */
    public function show(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);

        $theme = [];
        foreach (self::THEME_KEYS as $key) {
            $theme[$key] = config("settings.{$key}.value") ?? $this->defaults()[$key] ?? '';
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
            $value = $request->input($key, $defaults[$key] ?? '');

            // Guard theme_layout to only allow valid values
            if ($key === 'theme_layout' && ! in_array($value, ['1', '2', '3'], true)) {
                $value = '1';
            }

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

        Cache::forget('app_settings');

        return redirect()->back()->with('success', 'Theme settings updated successfully');
    }

    /**
     * Default values for each theme key.
     *
     * @return array<string, string>
     */
    private function defaults(): array
    {
        return [
            'theme_layout' => '1',
            'theme_color_mode' => 'light',
            'theme_direction' => 'ltr',
            'theme_color_palette' => 'blue',
            'theme_custom_color' => '#0c83ff',
            'theme_sidebar_color' => 'dark',
            'theme_sidebar_color_custom' => '',
            'theme_sidebar_type' => 'default',
            'theme_navbar_color' => 'dark',
            'theme_navbar_bg' => '',
            'theme_font_family' => 'inter',
        ];
    }

    /**
     * Setting type for each key.
     */
    private function keyType(string $key): string
    {
        return in_array($key, ['theme_navbar_bg', 'theme_custom_color', 'theme_sidebar_color_custom'], true) ? 'text' : 'select';
    }
}
