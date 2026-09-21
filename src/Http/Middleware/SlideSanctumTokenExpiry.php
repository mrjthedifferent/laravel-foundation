<?php

namespace Mrj\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class SlideSanctumTokenExpiry
{
    /**
     * Bump the current personal access token's expiry forward on each
     * authenticated request, giving Sanctum tokens a sliding (rolling) idle
     * window instead of a fixed lifetime. A continuously-used token never
     * expires; one left untouched for config('sanctum.idle_expiration')
     * minutes lapses and is rejected by the Sanctum guard.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        // Only a real persisted token slides. Sanctum::actingAs() (used in tests)
        // sets a Mockery mock of PersonalAccessToken whose expires_at is `false`,
        // so guard on ->exists and on expires_at actually being a date.
        if ($token instanceof PersonalAccessToken && $token->exists) {
            $window = apiTokenIdleExpirationMinutes();
            $throttle = 60; // write at most ~once/hour per token
            $expiresAt = $token->expires_at;

            if ($window > 0 &&
                (! $expiresAt instanceof \DateTimeInterface || $expiresAt->lt(now()->addMinutes($window - $throttle)))) {
                $token->forceFill(['expires_at' => now()->addMinutes($window)])->saveQuietly();
            }
        }

        return $next($request);
    }
}
