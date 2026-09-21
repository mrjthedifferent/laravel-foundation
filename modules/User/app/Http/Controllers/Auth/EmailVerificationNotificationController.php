<?php

namespace Modules\User\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mrj\Foundation\Http\Controllers\Controller;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $verified = ! empty($user->email_verified_at);

        if ($verified) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        if (empty($user->email)) {
            return back()->with('error', 'No email address is associated with your account.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent successfully');
    }
}
