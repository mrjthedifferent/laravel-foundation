<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\User\Actions\LoginAction;
use Modules\User\Actions\ManageUserAccountAction;
use Modules\User\Enum\AccountAction;
use Mrj\Foundation\Support\TwoFactorAuthenticator;

/**
 * The public page where anyone can delete their own account without the app: app stores
 * (Google Play) require such a web link next to in-app deletion. The person proves who they
 * are with their sign-in (email or phone and password, plus a two-factor code when they use
 * one). Throttled like sign-in. Super Admin accounts are not deleted here.
 * Turn it off with foundation.routing.account_deletion_page = false.
 */
final class AccountDeletionController extends Controller
{
    public function show(): View
    {
        return view('user::public.delete-account');
    }

    public function destroy(
        Request $request,
        LoginAction $login,
        TwoFactorAuthenticator $twoFactor,
        ManageUserAccountAction $manage,
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

        if ($user->is_super_admin) {
            return back()->withInput($request->only('login'))
                ->withErrors(['login' => __('user::user.account_deletion.super_admin')]);
        }

        if (! $manage->execute($user, AccountAction::Delete)) {
            return back()->withInput($request->only('login'))
                ->withErrors(['login' => __('user::user.errors.manage_account_failed_api')]);
        }

        $user->tokens()->delete();

        return redirect()->route('account.delete')->with('account_deleted', true);
    }
}
