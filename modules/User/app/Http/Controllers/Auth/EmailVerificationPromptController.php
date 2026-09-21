<?php

namespace Modules\User\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Mrj\Foundation\Http\Controllers\Controller;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the verification prompt.
     * Passes if the email is verified.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        $verified = ! empty($user->email_verified_at);

        return $verified
            ? redirect()->intended(route('admin.dashboard', absolute: false))
            : view('auth.verify-email');
    }
}
