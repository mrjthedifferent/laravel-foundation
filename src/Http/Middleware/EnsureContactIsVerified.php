<?php

namespace Mrj\Foundation\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom replacement for Laravel's built-in EnsureEmailIsVerified middleware.
 *
 * Passes if the authenticated user has verified either their email or their
 * phone number, so an account that signs in by phone only is not locked out.
 */
final class EnsureContactIsVerified
{
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        $user = $request->user();

        if (! $user instanceof MustVerifyEmail) {
            return $next($request);
        }

        if (! empty($user->email_verified_at) || ! empty($user->phone_verified_at)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return JsonResponseFactory::forbidden(__('foundation::foundation.auth.contact_not_verified'));
        }

        return redirect()->guest(route($redirectToRoute ?: 'verification.notice'));
    }
}
