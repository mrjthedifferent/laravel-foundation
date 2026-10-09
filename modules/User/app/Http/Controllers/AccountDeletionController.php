<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\User\Actions\LoginAction;
use Modules\User\Enum\DeletionStatus;
use Modules\User\Exceptions\AccountDeletionBlocked;
use Modules\User\Services\AccountDeletion;
use Mrj\Foundation\Support\TwoFactorAuthenticator;

/**
 * The public page where anyone can ask for their account to be deleted without the app: app
 * stores (Google Play) require such a web link next to in-app deletion. The person proves who
 * they are with their sign-in (email or phone and password, plus a two-factor code when they
 * use one). Throttled like sign-in. The request follows the same rules as in the app: blockers,
 * staff review when automatic deletion is off, and the grace period.
 * Turn it off with foundation.routing.account_deletion_page = false.
 */
final class AccountDeletionController extends Controller
{
    public function show(): View
    {
        return view('user::public.delete-account', [
            'graceDays' => (int) config('foundation.account_deletion.grace_days', 30),
        ]);
    }

    public function destroy(
        Request $request,
        LoginAction $login,
        TwoFactorAuthenticator $twoFactor,
        AccountDeletion $deletion,
    ): RedirectResponse {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string'],
            'two_factor_code' => ['nullable', 'string', 'max:64'],
            'confirm' => ['accepted'],
        ]);

        $user = $login->execute($data['login'], $data['password']);
        if ($user === null) {
            return back()->withInput($request->only('login'))
                ->withErrors(['login' => __('user::user.account_deletion.wrong_credentials')]);
        }

        if (config('foundation.two_factor.enabled') && $user->hasTwoFactorEnabled()
            && ! $twoFactor->verify($user, (string) ($data['two_factor_code'] ?? ''))) {
            return back()->withInput($request->only('login'))
                ->withErrors(['two_factor_code' => __('user::user.account_deletion.two_factor_needed')]);
        }

        try {
            $result = $deletion->request($user, 'web');
        } catch (AccountDeletionBlocked $e) {
            return back()->withInput($request->only('login'))->with('deletion_blockers', $e->blockers);
        }

        return redirect()->route('account.delete')->with('deletion', [
            'review' => $result->status === DeletionStatus::PendingReview,
            'date' => $result->scheduled_for?->toDateString(),
        ]);
    }
}
