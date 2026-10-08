<?php

declare(strict_types=1);

namespace Modules\Otp\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Otp\Actions\CompleteRegistrationAction;
use Modules\Otp\Actions\RequestRegistrationAction;
use Modules\Otp\Actions\ResetPasswordWithOtpAction;
use Modules\Otp\Actions\SendOtpAction;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Http\Requests\LoginWithOtpRequest;
use Modules\Otp\Http\Requests\RegisterRequest;
use Modules\Otp\Http\Requests\RequestLoginOtpRequest;
use Modules\Otp\Http\Requests\ResetPasswordWithOtpRequest;
use Modules\Otp\Support\OtpThrottle;
use Modules\User\Actions\TrackLoginAction;
use Modules\User\Transformers\UserResource;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;

/**
 * API sign-up with a phone confirmed by one-time code, and password reset by
 * one-time code. Sign-up is off unless `otp_self_registration_enabled` is on;
 * reset is off unless `otp_password_reset_enabled` is on.
 */
class OtpRegistrationController extends Controller
{
    public function requestRegistration(RegisterRequest $request, RequestRegistrationAction $action): JsonResponse
    {
        if (! $this->setting('otp_self_registration_enabled')) {
            return JsonResponseFactory::forbidden(__('otp::otp.errors.registration_disabled'));
        }

        $data = $request->validated();
        $refusal = $action->execute($data['phone'], $data);

        return $refusal === null
            ? JsonResponseFactory::success(__('otp::otp.success.code_sent'))
            : JsonResponseFactory::error(__($refusal), null, 429);
    }

    public function completeRegistration(
        LoginWithOtpRequest $request,
        CompleteRegistrationAction $action,
        TrackLoginAction $trackLogin,
    ): JsonResponse {
        if (! $this->setting('otp_self_registration_enabled')) {
            return JsonResponseFactory::forbidden(__('otp::otp.errors.registration_disabled'));
        }

        $user = $action->execute($request->validated('phone'), $request->validated('code'));

        if ($user === null) {
            return JsonResponseFactory::error(
                __('otp::otp.errors.invalid_or_expired_code'),
                ['code' => [__('otp::otp.errors.invalid_or_expired_code')]],
                422,
            );
        }

        $token = $user->createToken(
            $request->validated('device_name') ?: 'mobile',
            ['*'],
            now()->addMinutes(apiTokenIdleExpirationMinutes()),
        )->plainTextToken;

        $trackLogin->execute($user, $request);

        return JsonResponseFactory::created(__('user::user.flash.login_successful'), [
            'token' => $token,
            'user' => UserResource::make($user->load('roles')),
        ]);
    }

    public function forgotPassword(RequestLoginOtpRequest $request, SendOtpAction $sendOtp): JsonResponse
    {
        if (! $this->setting('otp_password_reset_enabled')) {
            return JsonResponseFactory::forbidden(__('otp::otp.errors.password_reset_disabled'));
        }

        $phone = (string) $request->validated('phone');
        $refusal = OtpThrottle::refusal($phone);

        if ($refusal !== null) {
            return JsonResponseFactory::error(__($refusal), null, 429);
        }

        // Same answer whether or not the number has an account.
        if (User::query()->wherePhone($phone)->exists()) {
            $sendOtp->execute($phone, ContactType::Phone);
        }

        return JsonResponseFactory::success(__('otp::otp.success.reset_code_sent'));
    }

    public function resetPassword(ResetPasswordWithOtpRequest $request, ResetPasswordWithOtpAction $action): JsonResponse
    {
        if (! $this->setting('otp_password_reset_enabled')) {
            return JsonResponseFactory::forbidden(__('otp::otp.errors.password_reset_disabled'));
        }

        $ok = $action->execute(
            (string) $request->validated('phone'),
            (string) $request->validated('code'),
            (string) $request->validated('password'),
        );

        return $ok
            ? JsonResponseFactory::success(__('otp::otp.success.password_reset'))
            : JsonResponseFactory::error(
                __('otp::otp.errors.invalid_or_expired_code'),
                ['code' => [__('otp::otp.errors.invalid_or_expired_code')]],
                422,
            );
    }

    private function setting(string $key): bool
    {
        return (bool) config("settings.{$key}.value", false);
    }
}
