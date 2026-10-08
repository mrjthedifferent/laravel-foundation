<?php

declare(strict_types=1);

namespace Modules\Otp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Otp\Actions\LoginWithOtpAction;
use Modules\Otp\Actions\RequestLoginOtpAction;
use Modules\Otp\Http\Requests\LoginWithOtpRequest;
use Modules\Otp\Http\Requests\RequestLoginOtpRequest;
use Modules\User\Actions\TrackLoginAction;
use Modules\User\Transformers\UserResource;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;
use Mrj\Foundation\Support\PhoneNumber;

/**
 * Passwordless phone sign-in for API clients. Off unless the
 * `otp_login_enabled` setting is on.
 */
class OtpLoginController extends Controller
{
    public function request(RequestLoginOtpRequest $request, RequestLoginOtpAction $action): JsonResponse
    {
        if (! $this->enabled()) {
            return JsonResponseFactory::forbidden(__('otp::otp.errors.login_disabled'));
        }

        $refusal = $action->execute((string) PhoneNumber::toE164($request->validated('phone')));

        if ($refusal !== null) {
            return JsonResponseFactory::error(__($refusal), null, 429);
        }

        return JsonResponseFactory::success(__('otp::otp.success.login_code_sent'));
    }

    public function verify(
        LoginWithOtpRequest $request,
        LoginWithOtpAction $action,
        TrackLoginAction $trackLogin,
    ): JsonResponse {
        if (! $this->enabled()) {
            return JsonResponseFactory::forbidden(__('otp::otp.errors.login_disabled'));
        }

        $result = $action->execute(
            (string) PhoneNumber::toE164($request->validated('phone')),
            $request->validated('code'),
            $request->validated('name'),
        );

        if ($result === null) {
            return JsonResponseFactory::error(
                __('otp::otp.errors.invalid_or_expired_code'),
                ['code' => [__('otp::otp.errors.invalid_or_expired_code')]],
                422,
            );
        }

        $user = $result['user'];

        if (! $user->is_active) {
            return JsonResponseFactory::forbidden(__('user::user.errors.account_not_active'));
        }

        $token = $user->createToken(
            $request->validated('device_name') ?: 'mobile',
            ['*'],
            now()->addMinutes(apiTokenIdleExpirationMinutes()),
        )->plainTextToken;

        $trackLogin->execute($user, $request);

        return JsonResponseFactory::success(__('user::user.flash.login_successful'), [
            'token' => $token,
            'user' => UserResource::make($user->load('roles')),
            'is_new_user' => $result['is_new_user'],
        ]);
    }

    private function enabled(): bool
    {
        return (bool) config('settings.otp_login_enabled.value', false);
    }
}
