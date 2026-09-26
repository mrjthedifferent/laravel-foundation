<?php

namespace Mrj\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mrj\Foundation\Contracts\ImpersonationContext;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces a user whose password an administrator set (ResetPasswordAction sets
 * must_change_password) to choose their own before touching anything else.
 * Runs on every web and API request, the same way CheckUserIsActive does,
 * so it cannot be bypassed by deep-linking past the profile page. Not while
 * someone is impersonating them: the new password is the user's to choose.
 */
final class EnsurePasswordIsChanged
{
    /**
     * Routes the user must still be able to reach: the page that lets them
     * change their password, the endpoint that saves it, and logout.
     *
     * @var list<string>
     */
    private const array ALLOWED_ROUTES = ['admin.profile.edit', 'password.update', 'logout'];

    public function __construct(private readonly ImpersonationContext $impersonation) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password || $this->impersonation->isImpersonating()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return JsonResponseFactory::forbidden(__('foundation::foundation.auth.password_change_required'));
        }

        return redirect()->route('admin.profile.edit')->with('error', __('foundation::foundation.auth.password_change_required'));
    }
}
