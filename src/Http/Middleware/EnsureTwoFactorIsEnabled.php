<?php

namespace Mrj\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mrj\Foundation\Contracts\ImpersonationContext;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a user who must use two-factor authentication (User::requiresTwoFactor())
 * but has not turned it on to their profile to set it up, before anything else.
 * Runs on every web and API request, like EnsurePasswordIsChanged. An
 * impersonated session is let through: the impersonator signed in themselves.
 */
final class EnsureTwoFactorIsEnabled
{
    /**
     * What the user must still reach: the profile page and its two-factor
     * setup, the password confirmation setup asks for, and logout.
     *
     * @var list<string>
     */
    private const array ALLOWED_ROUTES = [
        'admin.profile.edit',
        'admin.profile.two-factor.enable',
        'admin.profile.two-factor.confirm',
        'admin.profile.two-factor.recovery-codes',
        'password.confirm',
        'password.confirm.store',
        'password.update',
        'admin.impersonation.leave',
        'logout',
    ];

    public function __construct(private readonly ImpersonationContext $impersonation) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('foundation.two_factor.enabled')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || $user->hasTwoFactorEnabled() || ! $user->requiresTwoFactor()) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true) || $this->impersonation->isImpersonating()) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return JsonResponseFactory::forbidden(__('foundation::foundation.auth.two_factor_required'));
        }

        return redirect()->to(route('admin.profile.edit').'#two-factor')->with('error', __('foundation::foundation.auth.two_factor_required'));
    }
}
