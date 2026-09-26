<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\User\Actions\ConfirmTwoFactorAction;
use Modules\User\Actions\DisableTwoFactorAction;
use Modules\User\Actions\EnableTwoFactorAction;
use Modules\User\Actions\RegenerateRecoveryCodesAction;
use Mrj\Foundation\Http\Controllers\Controller;

/**
 * Two-factor authentication from the user's own profile, and the
 * administrator's reset for a user who lost their authenticator.
 */
class TwoFactorController extends Controller
{
    public function store(Request $request, EnableTwoFactorAction $action): RedirectResponse
    {
        $this->ensureEnabled();

        $action->execute($request->user());

        return redirect()->to(route('admin.profile.edit').'#two-factor');
    }

    public function confirm(Request $request, ConfirmTwoFactorAction $action): RedirectResponse
    {
        $this->ensureEnabled();

        $request->validate(['code' => ['required', 'string', 'max:20']]);

        $user = $request->user();

        if (! $action->execute($user, $request->string('code')->value())) {
            return redirect()->to(route('admin.profile.edit').'#two-factor')
                ->withErrors(['code' => __('user::user.two_factor.invalid_code')], 'twoFactor');
        }

        return redirect()->to(route('admin.profile.edit').'#two-factor')
            ->with('success', __('user::user.two_factor.enabled'))
            ->with('two_factor_recovery_codes', $user->two_factor_recovery_codes);
    }

    public function recoveryCodes(Request $request, RegenerateRecoveryCodesAction $action): RedirectResponse
    {
        $this->ensureEnabled();

        abort_unless($request->user()->hasTwoFactorEnabled(), 404);

        return redirect()->to(route('admin.profile.edit').'#two-factor')
            ->with('success', __('user::user.two_factor.recovery_codes_regenerated'))
            ->with('two_factor_recovery_codes', $action->execute($request->user()));
    }

    public function destroy(Request $request, DisableTwoFactorAction $action): RedirectResponse
    {
        $user = $request->user();

        // Someone who must use it can only replace it (enable again), not drop it.
        if ($user->hasTwoFactorEnabled() && $user->requiresTwoFactor()) {
            return back()->with('error', __('user::user.two_factor.required_cannot_disable'));
        }

        $action->execute($user);

        return redirect()->to(route('admin.profile.edit').'#two-factor')->with('success', __('user::user.two_factor.disabled'));
    }

    /**
     * An administrator turns off another user's two-factor authentication.
     */
    public function reset(User $user, DisableTwoFactorAction $action): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $action->execute($user);

        return back()->with('success', __('user::user.two_factor.reset'));
    }

    private function ensureEnabled(): void
    {
        abort_unless((bool) config('foundation.two_factor.enabled'), 404);
    }
}
