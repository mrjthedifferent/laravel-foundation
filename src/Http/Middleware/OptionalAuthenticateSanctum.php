<?php

namespace Mrj\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class OptionalAuthenticateSanctum
{
    /**
     * Authenticate the request when a Bearer token is present, without requiring it.
     * This allows routes to behave differently for authenticated users (e.g. include is_favourite).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            Auth::setUser($user);
        }

        return $next($request);
    }
}
