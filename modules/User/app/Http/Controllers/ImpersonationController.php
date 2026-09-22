<?php

namespace Modules\User\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\User\Actions\StartImpersonationAction;
use Modules\User\Actions\StopImpersonationAction;
use Mrj\Foundation\Http\Controllers\Controller;

/**
 * Lets a Super Admin sign in as another user and return to their own account.
 */
class ImpersonationController extends Controller
{
    /**
     * Start impersonating the given user.
     */
    public function store(Request $request, User $user, StartImpersonationAction $action): RedirectResponse
    {
        $this->authorize('impersonate', $user);

        $action->execute($request->user(), $user);

        return redirect()->route('admin.dashboard')
            ->with('info', "You are now signed in as {$user->name}.");
    }

    /**
     * Stop impersonating and return to the Super Admin's own account.
     */
    public function destroy(StopImpersonationAction $action): RedirectResponse
    {
        $this->authorize('leaveImpersonation', User::class);

        $user = $action->execute();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'You are back in your own account.');
    }
}
