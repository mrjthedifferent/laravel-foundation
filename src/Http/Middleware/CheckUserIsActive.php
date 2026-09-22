<?php

namespace Mrj\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mrj\Foundation\Contracts\ImpersonationContext;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Mrj\Foundation\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks out authenticated users whose account is inactive, or whom the project
 * denies access for its own reasons (User::accessDenialMessage()). Runs on every
 * web and API request so a status change takes effect on the user's very next
 * request — live sessions are logged out and API tokens revoked without waiting
 * for logout. A Super Admin impersonating such a user is returned to their own
 * account.
 */
final class CheckUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $message = $this->denialMessage($user);

        if ($message === null) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            // Push tokens are a User-only concern; third-party clients and other
            // tokenables just have their API tokens revoked.
            if ($user instanceof User) {
                $user->revokePushTokens();
            }
            $user->tokens()->delete();

            return JsonResponseFactory::forbidden($message);
        }

        $impersonation = app(ImpersonationContext::class);

        if ($impersonation->isImpersonating()) {
            $impersonation->stop();

            return redirect()->to(Route::has('admin.users.index') ? route('admin.users.index') : url('/'))
                ->with('error', __('foundation::foundation.auth.impersonation_ended', ['name' => $user->name]));
        }

        Auth::logout();

        return redirect()->route('login')->withErrors([$message]);
    }

    /**
     * Why the user must be denied access, or null when they may proceed.
     */
    private function denialMessage(mixed $user): ?string
    {
        if ($user->is_active !== true) {
            return __('foundation::foundation.auth.inactive');
        }

        return $user instanceof User ? $user->accessDenialMessage() : null;
    }
}
