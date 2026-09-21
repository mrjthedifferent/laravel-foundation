<?php

namespace Modules\User\Http\Controllers\Auth;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Modules\User\Actions\HandleSocialUserAction;
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
    public function callback(string $provider, HandleSocialUserAction $action): RedirectResponse
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
            return redirect()->route('login')->with('error', 'Authentication failed. Please try again.');
        }

        $user = $action->execute($provider, $socialUser);

        // Closed registration: social login never creates accounts. If no
        // matching account exists, reject the login.
        if (! $user) {
            return redirect()->route('login')->with('error', 'No account is associated with this login. Please contact an administrator.');
        }

        if (! $user->is_active) {
            return redirect()->route('login')->with('error', 'Your account is not active. Please contact support.');
        }

        if ($message = $user->accessDenialMessage()) {
            return redirect()->route('login')->with('error', $message);
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
