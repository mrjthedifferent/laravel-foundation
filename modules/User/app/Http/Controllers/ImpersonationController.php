<?php

namespace Modules\User\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        try {
            $action->execute($request->user(), $user);
        } catch (Exception $e) {
            Log::error('Impersonation start failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Failed to sign in as this user');
        }

        return redirect()->route('admin.dashboard')
            ->with('info', "You are now signed in as {$user->name}.");
    }

    /**
     * Stop impersonating and return to the Super Admin's own account.
     */
    public function destroy(StopImpersonationAction $action): RedirectResponse
    {
        $this->authorize('leaveImpersonation', User::class);

        try {
            $user = $action->execute();
        } catch (Exception $e) {
            Log::error('Impersonation stop failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Failed to return to your account');
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'You are back in your own account.');
    }
}
