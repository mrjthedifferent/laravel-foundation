<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\User\Actions\TrackLoginAction;
use Modules\User\Services\PendingTwoFactorLogin;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Support\TwoFactorAuthenticator;

/**
 * The second step of signing in for users with two-factor authentication:
 * a code from their authenticator app, or one of their recovery codes.
 */
class TwoFactorChallengeController extends Controller
{
    private const int MAX_ATTEMPTS = 5;

    public function create(Request $request, PendingTwoFactorLogin $pending): View|RedirectResponse
    {
        if ($pending->user($request) === null) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(
        Request $request,
        PendingTwoFactorLogin $pending,
        TwoFactorAuthenticator $authenticator,
        TrackLoginAction $trackLogin,
    ): RedirectResponse {
        $request->validate([
            'code' => ['nullable', 'string', 'max:20'],
            'recovery_code' => ['nullable', 'string', 'max:40'],
        ]);

        $user = $pending->user($request);

        if ($user === null) {
            return redirect()->route('login')->with('error', __('user::user.two_factor.expired'));
        }

        $throttleKey = 'two-factor:'.$user->getKey().'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'code' => trans('auth.throttle', ['seconds' => $seconds = RateLimiter::availableIn($throttleKey), 'minutes' => ceil($seconds / 60)]),
            ]);
        }

        $passed = $request->filled('recovery_code')
            ? $authenticator->useRecoveryCode($user, $request->string('recovery_code')->value())
            : $authenticator->verify($user, $request->string('code')->value());

        if (! $passed) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                $request->filled('recovery_code') ? 'recovery_code' : 'code' => __('user::user.two_factor.invalid_code'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        $remember = $pending->remember($request);
        $pending->clear($request);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $trackLogin->execute($user, $request);

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }
}
