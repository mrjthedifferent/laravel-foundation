<?php

namespace Modules\User\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires the password to have been confirmed within `auth.password_timeout`
 * before a sensitive POST (e.g. starting impersonation).
 *
 * Laravel's `password.confirm` middleware returns to the intended URL with a
 * GET, which cannot replay a POST — so this sends the user back to the page
 * they came from to repeat the action once their password is confirmed.
 */
class RequireRecentPasswordConfirmation
{
    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);

        if (time() - $confirmedAt < (int) config('auth.password_timeout', 10800)) {
            return $next($request);
        }

        redirect()->setIntendedUrl(url()->previous());

        return redirect()->route('password.confirm');
    }
}
