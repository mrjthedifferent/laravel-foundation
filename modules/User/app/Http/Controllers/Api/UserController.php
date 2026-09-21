<?php

namespace Modules\User\Http\Controllers\Api;

use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Modules\Otp\Actions\VerifyOtpAction;
use Modules\Otp\Enum\ContactType;
use Modules\User\Actions\ApiResetPasswordAction;
use Modules\User\Actions\ChangePasswordAction;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Actions\HandleSocialUserAction;
use Modules\User\Actions\LoginAction;
use Modules\User\Actions\ManageUserAccountAction;
use Modules\User\Actions\MarkContactVerifiedAction;
use Modules\User\Actions\OtpLoginAction;
use Modules\User\Actions\TrackLoginAction;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Data\UserData;
use Modules\User\Enum\AccountAction;
use Modules\User\Http\Requests\ChangePasswordRequest;
use Modules\User\Http\Requests\LoginRequest;
use Modules\User\Http\Requests\ManageUserAccountRequest;
use Modules\User\Http\Requests\RegisterRequest;
use Modules\User\Http\Requests\ResetPasswordRequest;
use Modules\User\Http\Requests\UpdateProfileRequest;
use Modules\User\Http\Requests\VerifyLoginOtpRequest;
use Modules\User\Transformers\UserResource;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Spatie\Permission\Models\Role;

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

        return JsonResponseFactory::success('User details', UserResource::make($user));
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
            return JsonResponseFactory::unauthorized('Invalid credentials');
        }

        if (! $user->is_active) {
            return JsonResponseFactory::forbidden('Account is not active. Contact support.');
        }

        $token = $user->createToken('authToken', ['*'], now()->addMinutes(apiTokenIdleExpirationMinutes()))->plainTextToken;

        $trackLoginAction->execute($user, $request);

        return JsonResponseFactory::success('Login successful', [
            'token' => $token,
            'user' => UserResource::make($user->load('roles')),
        ]);
    }

    /**
     * Register
     */
    public function register(
        RegisterRequest $request,
        CreateUserAction $action,
        VerifyOtpAction $verifyOtp,
        TrackLoginAction $trackLoginAction,
        MarkContactVerifiedAction $markVerified
    ): JsonResponse {
        $role = Role::where('name', $request->validated('role'))->firstOrFail();
        $validated = array_merge($request->validated(), ['roles' => [$role->id]]);

        if ($email = $request->validated('email')) {
            if (! $verifyOtp->execute($email, $request->validated('email_code'))) {
                return JsonResponseFactory::error('Invalid email verification code', null, 400);
            }
        }

        $user = $action->execute(UserData::from($validated));

        // OTP codes were already consumed/verified above; stamp verified_at via dedicated action.
        if ($email) {
            $markVerified->execute($user, 'email');
        }

        $token = $user->createToken('authToken', ['*'], now()->addMinutes(apiTokenIdleExpirationMinutes()))->plainTextToken;

        $trackLoginAction->execute($user, $request);

        return JsonResponseFactory::success('Registration successful', [
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
        VerifyOtpAction $verifyOtp,
        MarkContactVerifiedAction $markVerified
    ): JsonResponse {
        $data = $request->validated();

        if (! empty($data['email']) && ! $verifyOtp->execute($data['email'], $data['email_code'] ?? '')) {
            return JsonResponseFactory::error('Email not verified', null, 400);
        }

        $user = $action->execute($request->user()->id, UserData::from($data));

        // OTP codes were already consumed/verified above; stamp verified_at via dedicated action.
        if (! empty($data['email'])) {
            $markVerified->execute($user, 'email');
        }

        return JsonResponseFactory::success('Profile updated successfully', UserResource::make($user->fresh('roles')));
    }

    /**
     * Reset password (OTP-verified, unauthenticated flow)
     */
    public function resetPassword(
        ResetPasswordRequest $request,
        VerifyOtpAction $verifyOtp,
        ApiResetPasswordAction $resetAction
    ): JsonResponse {
        if (! $verifyOtp->execute($request->validated('contact'), $request->validated('code'))) {
            return JsonResponseFactory::error('Invalid verification code', null, 400);
        }

        $user = User::where($request->validated('contact_type'), $request->validated('contact'))->first();
        if (! $user) {
            return JsonResponseFactory::notFound('User not found');
        }

        $resetAction->execute($user, $request->validated('contact_type'), $request->validated('password'));

        return JsonResponseFactory::success('Password reset successful');
    }

    /**
     * Verify OTP and log in (auto-registers new users with a random password).
     */
    public function loginWithOtp(
        VerifyLoginOtpRequest $request,
        VerifyOtpAction $verifyOtp,
        OtpLoginAction $otpLogin,
        TrackLoginAction $trackLoginAction
    ): JsonResponse {
        $data = $request->validated();
        $contact = $data['id'];
        $contactType = ContactType::detect($contact);

        if (! $verifyOtp->execute($contact, $data['code'])) {
            return JsonResponseFactory::error('Invalid or expired verification code.', null, 400);
        }

        $user = $otpLogin->execute($contact, $contactType, $data['role'] ?? null);

        if (! $user->is_active) {
            return JsonResponseFactory::forbidden('Account is not active. Contact support.');
        }

        $token = $user->createToken('authToken', ['*'], now()->addMinutes(apiTokenIdleExpirationMinutes()))->plainTextToken;

        $trackLoginAction->execute($user, $request);

        return JsonResponseFactory::success('Login successful', [
            'token' => $token,
            'user' => UserResource::make($user->load('roles')),
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return JsonResponseFactory::unauthorized('User not authenticated');
        }

        $user->firebaseTokens()->delete();

        $this->revokeCurrentToken($user);

        return JsonResponseFactory::success('Logout successful');
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
                return JsonResponseFactory::forbidden('Account management unavailable in production');
            }
        }

        if (! Hash::check($request->validated('password'), $request->user()->password)) {
            return JsonResponseFactory::unauthorized('Invalid password');
        }

        $accountAction = AccountAction::from($request->validated('action'));
        $result = $action->execute($user, $accountAction);

        if (! $result) {
            return JsonResponseFactory::serverError('Failed to manage account');
        }

        $message = $accountAction === AccountAction::Reset
            ? 'Account reset successfully'
            : 'Account deleted successfully';

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
            return JsonResponseFactory::error('Current password is incorrect', null, 400);
        }

        $action->execute($request->user(), $request->validated('password'));

        return JsonResponseFactory::success('Password changed successfully');
    }

    /**
     * Social authentication
     */
    public function socialAuth(
        Request $request,
        HandleSocialUserAction $action,
        TrackLoginAction $trackLoginAction
    ): JsonResponse {
        $data = $request->validate($this->socialAuthRules());

        try {
            $socialUser = Socialite::driver($data['provider'])
                ->stateless()
                ->userFromToken($data['access_token']);
        } catch (Exception $e) {
            Log::error('Social auth failed', [
                'provider' => $data['provider'],
                'error' => $e->getMessage(),
            ]);

            return JsonResponseFactory::unauthorized('Social authentication failed. Check credentials.');
        }

        $user = $action->execute($data['provider'], $socialUser, $data['role']);

        if (! $user->is_active) {
            return JsonResponseFactory::forbidden('Account is not active. Contact support.');
        }

        $token = $user->createToken('authToken', ['*'], now()->addMinutes(apiTokenIdleExpirationMinutes()))->plainTextToken;

        $trackLoginAction->execute($user, $request);

        return JsonResponseFactory::success('Authentication successful', [
            'token' => $token,
            'user' => UserResource::make($user->load('roles')),
        ]);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Social authentication validation rules.
     * Provider list comes from DB-driven roles so it stays in sync.
     */
    private function socialAuthRules(): array
    {
        $allowedRoles = Role::pluck('name')->toArray();

        return [
            'provider' => ['required', 'in:google,github,apple'],
            'access_token' => ['required', 'string'],
            'role' => ['required', Rule::in($allowedRoles)],
        ];
    }

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
