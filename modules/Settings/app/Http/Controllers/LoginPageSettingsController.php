<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Modules\Settings\Actions\SaveSettingAction;
use Modules\Settings\Http\Requests\UpdateLoginPageSettingsRequest;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Http\Controllers\Controller;

/**
 * Settings → Login page: the brand panel beside the sign-in form.
 *
 * The settings are declared in config/settings.php with 'seed' => false and a 'config'
 * target in foundation-login.*: SettingsConfigApplier copies the saved values onto those
 * keys at boot, so the panel keeps reading config(). A text left blank, or the bullets set
 * back to "default", deletes its row, so the config file's default (and then the
 * translation) shows again.
 */
class LoginPageSettingsController extends Controller
{
    private const string GROUP = 'Login page';

    public function show(SettingsRepository $settings): Renderable
    {
        $this->authorize('editSpecial', Setting::class);

        $app = mailAppName();
        $defaults = $this->defaults();
        $storedHighlights = $settings->get('login_panel_highlights');

        return view('settings::special.login-page', [
            // What is stored, so a blank field means "the default"
            'headline' => $settings->get('login_panel_headline'),
            'subheadline' => $settings->get('login_panel_subheadline'),
            'defaultHeadline' => __($defaults['headline'] ?? 'foundation::foundation.auth.tagline', ['app' => $app]),
            'defaultSubheadline' => __($defaults['subheadline'] ?? 'foundation::foundation.auth.tagline_meta', ['app' => $app]),
            'highlightsMode' => $storedHighlights === null ? 'default' : 'custom',
            // The editor starts from the saved bullets, or the defaults, in readable text
            'highlights' => $this->readable($storedHighlights ?? $defaults['highlights'] ?? []),
            'defaultHighlights' => $this->readable($defaults['highlights'] ?? []),
            'mark' => (string) config('foundation-login.mark', 'auto'),
            'showName' => (bool) config('foundation-login.show_name', true),
            'graphic' => (string) config('foundation-login.graphic', 'bars'),
            'maxHighlights' => UpdateLoginPageSettingsRequest::MAX_HIGHLIGHTS,
        ]);
    }

    public function update(UpdateLoginPageSettingsRequest $request, SettingsRepository $settings): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        $this->saveOrForget('login_panel_headline', trim((string) $request->validated('headline')), 'text');
        $this->saveOrForget('login_panel_subheadline', trim((string) $request->validated('subheadline')), 'text');

        if ($request->validated('highlights_mode') === 'custom') {
            // Rows without text are dropped; an empty list is kept, and hides the bullets.
            $rows = collect((array) $request->validated('highlights', []))
                ->map(fn (array $row): array => [
                    'icon' => filled($row['icon'] ?? null) ? (string) $row['icon'] : 'ph ph-check',
                    'text' => trim((string) ($row['text'] ?? '')),
                ])
                ->filter(fn (array $row): bool => $row['text'] !== '')
                ->values()
                ->all();

            $this->save('login_panel_highlights', $rows, 'json');
        } else {
            $this->forget('login_panel_highlights');
        }

        $this->save('login_panel_mark', (string) $request->validated('mark'), 'select', ['auto' => 'Auto', 'monogram' => 'Monogram', 'none' => 'None']);
        $this->save('login_panel_show_name', $request->boolean('show_name') ? '1' : '0', 'boolean');
        $this->save('login_panel_graphic', (string) $request->validated('graphic'), 'select', ['bars' => 'Bars', 'glow' => 'Glow', 'none' => 'None']);

        $settings->forget();

        return redirect()->route('admin.settings.special.login_page')
            ->with('success', __('settings::settings.flash.login_page_updated'));
    }

    /**
     * The panel's defaults as the config files give them, before any saved setting is
     * applied over config(): the package's file, overlaid with the project's published one.
     *
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        $project = config_path('foundation-login.php');

        return array_replace(
            (array) require Foundation::path('config/foundation-login.php'),
            is_file($project) ? (array) require $project : [],
        );
    }

    /**
     * Bullets as an admin reads them: translation keys turned into text.
     *
     * @return list<array{icon: string, text: string}>
     */
    private function readable(mixed $items): array
    {
        return collect((array) $items)
            ->filter(fn ($item) => is_array($item) && filled($item['text'] ?? null))
            ->map(fn (array $item): array => [
                'icon' => (string) ($item['icon'] ?? 'ph ph-check'),
                'text' => __((string) $item['text'], ['app' => mailAppName()]),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>|null  $options
     */
    private function save(string $key, mixed $value, string $type, ?array $options = null): void
    {
        $definition = (array) config('settings.settings.'.$key, []);

        SaveSettingAction::hidden($key, $value, $type, self::GROUP, [
            // Stored in English; the settings pages translate it at display time.
            'description' => $definition['description'] ?? null,
            'options' => $options !== null ? json_encode($options) : null,
            'is_required' => false,
        ]);
    }

    private function saveOrForget(string $key, string $value, string $type): void
    {
        $value === '' ? $this->forget($key) : $this->save($key, $value, $type);
    }

    private function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();
    }
}
