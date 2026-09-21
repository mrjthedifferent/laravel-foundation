<?php

namespace Modules\User\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Support\Email;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Email a reset link. The response is the same whether or not the address
     * belongs to an account, so the form cannot be used to discover users.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Email::normalize($request->input('email'))]);
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
