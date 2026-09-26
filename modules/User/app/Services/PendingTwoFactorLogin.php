<?php

declare(strict_types=1);

namespace Modules\User\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A sign-in whose password (or social account) checked out, held in the
 * session until the user also passes the two-factor challenge. Nobody is
 * signed in meanwhile; the hold expires after a few minutes.
 */
final class PendingTwoFactorLogin
{
    private const string KEY = 'two_factor.login';

    private const int TTL_SECONDS = 300;

    /**
     * Holds the sign-in and sends the user to the challenge when their account
     * has two-factor authentication on; null when they can be signed in now.
     */
    public function challenge(Request $request, User $user, bool $remember): ?RedirectResponse
    {
        if (! config('foundation.two_factor.enabled') || ! $user->hasTwoFactorEnabled()) {
            return null;
        }

        $request->session()->regenerate();
        $request->session()->put(self::KEY, [
            'id' => $user->getKey(),
            'remember' => $remember,
            'expires_at' => time() + self::TTL_SECONDS,
        ]);

        return redirect()->route('two-factor.login');
    }

    public function user(Request $request): ?User
    {
        $pending = $request->session()->get(self::KEY);

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < time()) {
            return null;
        }

        return User::query()->find($pending['id'] ?? null);
    }

    public function remember(Request $request): bool
    {
        return (bool) ($request->session()->get(self::KEY)['remember'] ?? false);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::KEY);
    }
}
