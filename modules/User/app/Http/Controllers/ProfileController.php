<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Data\UserData;
use Modules\User\Enum\Gender;
use Mrj\Foundation\Http\Controllers\Controller;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request, UpdateUserAction $action): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            // Create UserData from request + existing user data for required fields
            $userData = UserData::from([
                'name' => $validated['name'],
                'image' => $validated['image'] ?? null,
                // Preserve existing sensitive/required data
                'email' => $user->email,
                'gender' => $user->gender instanceof Gender ? $user->gender->value : $user->gender,
                'roles' => $user->roles->pluck('id')->toArray(),
                'is_active' => $user->is_active,
            ]);

            $action->execute($user->id, $userData);

            return Redirect::route('admin.profile.edit')->with('success', 'Your profile updated successfully');
        } catch (\Exception $e) {
            return Redirect::route('admin.profile.edit')->with('error', 'Failed to update profile: '.$e->getMessage());
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        return Redirect::back()->with('error', 'User Deletion is disabled.');
        //        $request->validateWithBag('userDeletion', [
        //            'password' => ['required', 'current_password'],
        //        ]);
        //
        //        $user = $request->user();
        //
        //        Auth::logout();
        //
        //        $user->delete();
        //
        //        $request->session()->invalidate();
        //        $request->session()->regenerateToken();
        //
        //        return Redirect::to('/');
    }
}
