<?php

namespace Modules\User\Http\Controllers\Auth;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Modules\User\Actions\HandleSocialUserAction;
use Modules\User\Services\PendingTwoFactorLogin;
use Mrj\Foundation\Http\Controllers\Controller;

class SocialAuthController extends Controller
{
    /**
     * Redirect to the provider's authentication page.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSocialAuthEnabled();
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the callback from the provider.
     */
    public function callback(string $provider, HandleSocialUserAction $action, Request $request, PendingTwoFactorLogin $twoFactor): RedirectResponse
    {
        $this->ensureSocialAuthEnabled();
        $this->validateProvider($provider);

        try {
            if ($provider === 'apple') {
                $socialUser = Socialite::driver($provider)->stateless()->user();
            } else {
                $socialUser = Socialite::driver($provider)->user();
            }
        } catch (Exception $e) {
            return redirect()->route('login')->with('error', __('user::user.auth.social_failed'));
        }

        $user = $action->execute($provider, $socialUser);

        // Closed registration: social login never creates accounts. If no
        // matching account exists, reject the login.
        if (! $user) {
            return redirect()->route('login')->with('error', __('user::user.auth.social_no_account'));
        }

        if (! $user->is_active) {
            return redirect()->route('login')->with('error', __('user::user.auth.social_account_not_active'));
        }

        if ($message = $user->accessDenialMessage()) {
            return redirect()->route('login')->with('error', $message);
        }

        if ($challenge = $twoFactor->challenge($request, $user, true)) {
            return $challenge;
        }

        Auth::login($user, true);

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Validate that the provider is supported.
     */
    protected function validateProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'github', 'apple'])) {
            abort(404);
        }
    }

    /**
     * Social login is disabled by default because registration is closed.
     * It must be explicitly enabled via settings to be reachable.
     */
    protected function ensureSocialAuthEnabled(): void
    {
        if (! (bool) config('settings.social_auth_enabled.value', false)) {
            abort(404);
        }
    }
}
