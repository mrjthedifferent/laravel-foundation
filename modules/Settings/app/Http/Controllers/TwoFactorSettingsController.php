<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Modules\Settings\Http\Requests\UpdateTwoFactorSettingsRequest;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

/**
 * Settings → Two-Factor: who may and who must use two-factor authentication.
 *
 * The four settings are created on the first save; until then the values in
 * config('foundation.two_factor') (and .env) apply. SettingsConfigApplier
 * copies the saved values onto that config at boot, so every reader of
 * foundation.two_factor.* follows this page.
 */
class TwoFactorSettingsController extends Controller
{
    public function show(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);

        return view('settings::special.two-factor', [
            'enabled' => (bool) config('foundation.two_factor.enabled'),
            'requiredForSuperAdmins' => (bool) config('foundation.two_factor.required_for_super_admins'),
            'requiredRoles' => (array) config('foundation.two_factor.required_roles', []),
            'issuer' => (string) config('foundation.two_factor.issuer'),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->pluck('name')->all(),
        ]);
    }

    public function update(UpdateTwoFactorSettingsRequest $request): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        $roles = array_values(array_unique((array) $request->validated('required_roles', [])));
        $allRoles = Role::where('guard_name', 'web')->orderBy('name')->pluck('name')->all();

        // Descriptions are stored in English; the settings pages translate them at display time.
        $settings = [
            'two_factor_enabled' => [
                'type' => 'boolean',
                'value' => $request->boolean('enabled') ? '1' : '0',
                'description' => 'Let users turn on two-factor authentication from their profile',
            ],
            'two_factor_required_for_super_admins' => [
                'type' => 'boolean',
                'value' => $request->boolean('required_for_super_admins') ? '1' : '0',
                'description' => 'Super admins must set up two-factor authentication before using the panel',
            ],
            'two_factor_required_roles' => [
                'type' => 'multi-select',
                'value' => implode(',', $roles),
                'options' => json_encode(array_combine($allRoles, $allRoles) ?: []),
                'description' => 'Roles whose users must set up two-factor authentication before using the panel',
            ],
            'two_factor_issuer' => [
                'type' => 'text',
                'value' => (string) $request->validated('issuer'),
                'description' => 'Name shown in the authenticator app (the app name when empty)',
            ],
        ];

        foreach ($settings as $key => $attributes) {
            Setting::updateOrCreate(
                ['key' => $key],
                $attributes + ['group' => 'Security', 'is_visible' => false, 'is_required' => false],
            );
        }

        app(SettingsRepository::class)->forget();

        return redirect()->route('admin.settings.special.two_factor')
            ->with('success', __('settings::settings.flash.two_factor_updated'));
    }
}
