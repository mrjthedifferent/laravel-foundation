<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Otp\Http\Controllers\OtpLoginController;
use Modules\Otp\Http\Controllers\OtpRegistrationController;
use Modules\Otp\Http\Controllers\OtpVerificationController;

Route::prefix(config('foundation.routing.api_prefix'))->group(function (): void {
    Route::post('send-verification-code', [OtpVerificationController::class, 'sendVerificationCode'])->middleware('throttle:10,1');
    Route::post('verify-otp', [OtpVerificationController::class, 'verifyCode'])->middleware('throttle:10,1');

    // Passwordless phone sign-in (off unless the otp_login_enabled setting is on).
    Route::post('auth/otp/request', [OtpLoginController::class, 'request'])->middleware('throttle:auth');
    Route::post('auth/otp/verify', [OtpLoginController::class, 'verify'])->middleware('throttle:auth');

    // Sign-up confirmed by a code to the phone (otp_self_registration_enabled),
    // and password reset by code (otp_password_reset_enabled).
    Route::post('auth/register/request', [OtpRegistrationController::class, 'requestRegistration'])->middleware('throttle:auth');
    Route::post('auth/register/verify', [OtpRegistrationController::class, 'completeRegistration'])->middleware('throttle:auth');
    Route::post('auth/password/forgot', [OtpRegistrationController::class, 'forgotPassword'])->middleware('throttle:auth');
    Route::post('auth/password/reset', [OtpRegistrationController::class, 'resetPassword'])->middleware('throttle:auth');
});
