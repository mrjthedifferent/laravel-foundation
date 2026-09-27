<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Modules\Settings\Http\Requests\UpdateSecuritySettingsRequest;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

/**
 * Settings → Security: two-factor authentication, the password rule, sign-in
 * lockout, session lifetime and API token lifetime.
 *
 * The settings are declared in config/settings.php with 'seed' => false and a
 * 'config' target: this page creates them on its first save, and until then
 * the config (and .env) decides. SettingsConfigApplier copies the saved
 * values onto their config keys at boot.
 */
class SecuritySettingsController extends Controller
{
    public function show(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);

        return view('settings::special.security', [
            'twoFactor' => [
                'enabled' => (bool) config('foundation.two_factor.enabled'),
                'required_for_super_admins' => (bool) config('foundation.two_factor.required_for_super_admins'),
                'required_roles' => (array) config('foundation.two_factor.required_roles', []),
                'issuer' => (string) config('foundation.two_factor.issuer'),
            ],
            'passwords' => [
                'min_length' => (int) config('foundation.passwords.min_length', 8),
                'mixed_case' => (bool) config('foundation.passwords.mixed_case'),
                'numbers' => (bool) config('foundation.passwords.numbers'),
                'symbols' => (bool) config('foundation.passwords.symbols'),
                'uncompromised' => (bool) config('foundation.passwords.uncompromised'),
            ],
            'maxAttempts' => (int) config('foundation.login.max_attempts', 5),
            'sessionLifetime' => (int) config('session.lifetime', 120),
            'apiTokenIdle' => apiTokenIdleExpirationMinutes(),
            'roles' => $this->roles(),
        ]);
    }

    public function update(UpdateSecuritySettingsRequest $request): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        $roles = $this->roles();
        $flag = fn (string $field): string => $request->boolean($field) ? '1' : '0';

        $values = [
            'two_factor_enabled' => $flag('two_factor_enabled'),
            'two_factor_required_for_super_admins' => $flag('two_factor_required_for_super_admins'),
            'two_factor_required_roles' => implode(',', array_values(array_unique((array) $request->validated('two_factor_required_roles', [])))),
            'two_factor_issuer' => (string) $request->validated('two_factor_issuer'),
            'password_min_length' => (string) $request->integer('password_min_length'),
            'password_mixed_case' => $flag('password_mixed_case'),
            'password_numbers' => $flag('password_numbers'),
            'password_symbols' => $flag('password_symbols'),
            'password_uncompromised' => $flag('password_uncompromised'),
            'login_max_attempts' => (string) $request->integer('login_max_attempts'),
            'session_lifetime' => (string) $request->integer('session_lifetime'),
            'api_token_idle_expiration_minutes' => (string) $request->integer('api_token_idle_expiration_minutes'),
        ];

        $definitions = (array) config('settings.settings', []);

        foreach ($values as $key => $value) {
            $definition = $definitions[$key] ?? [];

            Setting::updateOrCreate(['key' => $key], [
                'group' => $definition['group'] ?? 'Security',
                'type' => $definition['type'] ?? 'text',
                // Stored in English; the settings pages translate it at display time.
                'description' => $definition['description'] ?? null,
                'value' => $value,
                'options' => $key === 'two_factor_required_roles' ? json_encode(array_combine($roles, $roles) ?: []) : null,
                'is_visible' => false,
                'is_required' => false,
            ]);
        }

        app(SettingsRepository::class)->forget();

        return redirect()->route('admin.settings.special.security')
            ->with('success', __('settings::settings.flash.security_updated'));
    }

    /**
     * @return list<string>
     */
    private function roles(): array
    {
        return Role::where('guard_name', 'web')->orderBy('name')->pluck('name')->all();
    }
}
