<?php

namespace Mrj\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mrj\Foundation\Contracts\ImpersonationContext;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maintenance mode for the web panel: while the `maintenance_mode` setting is
 * on, a signed-in user who is not a super admin gets a 503 page
 * (errors/maintenance.blade.php) carrying the `maintenance_message` setting,
 * and a way to sign out. Guests pass, so a super admin can still sign in
 * (every panel page needs a sign-in anyway).
 * An impersonated session passes: the impersonator is a super admin or an
 * administrator trusted with it. The API is left alone; the mobile app has its
 * own `app_maintenance_mode`.
 */
final class EnsurePanelIsAvailable
{
    /**
     * @var list<string>
     */
    private const array ALLOWED_ROUTES = [
        'logout',
        'admin.impersonation.leave',
    ];

    public function __construct(private readonly ImpersonationContext $impersonation) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('settings.maintenance_mode.value')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || $user->isSuperAdmin() || $this->impersonation->isImpersonating()
            || in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        $message = (string) (config('settings.maintenance_message.value') ?: __('foundation::foundation.errors.maintenance'));

        if ($request->expectsJson()) {
            return JsonResponseFactory::error($message, code: 503);
        }

        return response()->view('errors.maintenance', ['maintenanceMessage' => $message], 503);
    }
}
