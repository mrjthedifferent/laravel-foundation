<?php

namespace Modules\User\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\User\Http\Requests\Auth\LoginRequest;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Support\PhoneNumber;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $login = (string) $request->input('login');

        // Email matches users.email; anything else is treated as a phone number,
        // which the project's user model knows how to look up (User::scopeWherePhone()).
        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $login)->first()
            : User::query()->wherePhone(PhoneNumber::toE164($login))->first();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Invalid credentials');
        }

        if ($user->is_active !== true) {
            return redirect()->route('login')->with('error', 'Your account is not active');
        }

        if ($message = $user->accessDenialMessage()) {
            return redirect()->route('login')->with('error', $message);
        }

        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
