<?php

namespace Modules\User\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\User\Actions\ChangePasswordAction;
use Modules\User\Actions\LoginAction;
use Modules\User\Actions\ManageUserAccountAction;
use Modules\User\Actions\MarkContactVerifiedAction;
use Modules\User\Actions\TrackLoginAction;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Data\UserData;
use Modules\User\Enum\AccountAction;
use Modules\User\Http\Requests\ChangePasswordRequest;
use Modules\User\Http\Requests\LoginRequest;
use Modules\User\Http\Requests\ManageUserAccountRequest;
use Modules\User\Http\Requests\UpdateProfileRequest;
use Modules\User\Transformers\UserResource;
use Mrj\Foundation\Contracts\OtpVerifier;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;

/**
 * API Controller for User Management
 *
 * ARCHITECTURE PATTERN: Ultra-Thin API Controller
 * - Validates requests via Form Requests
 * - Executes business logic via Action classes
 * - Returns JSON via JsonResponseFactory
 */
class UserController extends Controller
{
    /**
     * Get authenticated user details
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles', 'documents');

        return JsonResponseFactory::success(__('user::user.flash.user_details'), UserResource::make($user));
    }

    /**
     * Login
     */
    public function login(
        LoginRequest $request,
        LoginAction $loginAction,
        TrackLoginAction $trackLoginAction
    ): JsonResponse {
        $user = $loginAction->execute(
            $request->validated('id'),
            $request->validated('password')
        );

        if (! $user) {
            return JsonResponseFactory::unauthorized(__('user::user.errors.invalid_credentials'));
        }

        if (! $user->is_active) {
            return JsonResponseFactory::forbidden(__('user::user.errors.account_not_active'));
        }

        $token = $user->createToken('authToken', ['*'], now()->addMinutes(apiTokenIdleExpirationMinutes()))->plainTextToken;

        $trackLoginAction->execute($user, $request);

        return JsonResponseFactory::success(__('user::user.flash.login_successful'), [
            'token' => $token,
            'user' => UserResource::make($user->load('roles')),
        ]);
    }

    /**
     * Update profile
     */
    public function update(
        UpdateProfileRequest $request,
        UpdateUserAction $action,
        OtpVerifier $verifyOtp,
        MarkContactVerifiedAction $markVerified
    ): JsonResponse {
        $data = $request->validated();

        if (! empty($data['email']) && ! $verifyOtp->execute($data['email'], $data['email_code'] ?? '')) {
            return JsonResponseFactory::error(__('user::user.errors.email_not_verified'), null, 400);
        }

        $user = $action->execute($request->user()->id, UserData::from($data));

        // OTP codes were already consumed/verified above; stamp verified_at via dedicated action.
        if (! empty($data['email'])) {
            $markVerified->execute($user, 'email');
        }

        return JsonResponseFactory::success(__('user::user.flash.profile_updated'), UserResource::make($user->fresh('roles')));
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return JsonResponseFactory::unauthorized(__('user::user.errors.user_not_authenticated'));
        }

        $user->firebaseTokens()->delete();

        $this->revokeCurrentToken($user);

        return JsonResponseFactory::success(__('user::user.flash.logout_successful'));
    }

    /**
     * Manage account (reset/delete). With no user_id this is self-service
     * (a user resetting/deleting their own account); a user_id targeting
     * someone else is the admin path and requires the same 'Delete User'
     * permission and production guard the web equivalent enforces — without
     * this check, any authenticated user could delete another user's account
     * by supplying their own password and an arbitrary user_id.
     */
    public function manageAccount(
        ManageUserAccountRequest $request,
        ManageUserAccountAction $action
    ): JsonResponse {
        $userId = $request->validated('user_id') ?? $request->user()->id;
        $user = User::findOrFail($userId);

        if ((int) $userId !== $request->user()->id) {
            Gate::authorize('manageAccount', $user);

            if (app()->environment('production')) {
                return JsonResponseFactory::forbidden(__('user::user.errors.account_management_unavailable'));
            }
        }

        if (! Hash::check($request->validated('password'), $request->user()->password)) {
            return JsonResponseFactory::unauthorized(__('user::user.errors.invalid_password'));
        }

        $accountAction = AccountAction::from($request->validated('action'));
        $result = $action->execute($user, $accountAction);

        if (! $result) {
            return JsonResponseFactory::serverError(__('user::user.errors.manage_account_failed_api'));
        }

        $message = $accountAction === AccountAction::Reset
            ? __('user::user.flash.account_reset_api')
            : __('user::user.flash.account_deleted_api');

        if ($accountAction === AccountAction::Delete && (int) $userId === $request->user()->id) {
            $request->user()->tokens()->delete();
        }

        return JsonResponseFactory::success($message);
    }

    /**
     * Change password (authenticated flow)
     */
    public function changePassword(
        ChangePasswordRequest $request,
        ChangePasswordAction $action
    ): JsonResponse {
        if (! Hash::check($request->validated('current_password'), $request->user()->password)) {
            return JsonResponseFactory::error(__('user::user.errors.current_password_incorrect'), null, 400);
        }

        $action->execute($request->user(), $request->validated('password'));

        return JsonResponseFactory::success(__('user::user.flash.password_changed_successfully'));
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Safely revoke the current access token
     */
    private function revokeCurrentToken(User $user): void
    {
        /** @var PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
