<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Otp\Http\Controllers\OtpLoginController;
use Modules\Otp\Http\Controllers\OtpVerificationController;

Route::prefix(config('foundation.routing.api_prefix'))->group(function (): void {
    Route::post('send-verification-code', [OtpVerificationController::class, 'sendVerificationCode'])->middleware('throttle:10,1');
    Route::post('verify-otp', [OtpVerificationController::class, 'verifyCode'])->middleware('throttle:10,1');

    // Passwordless phone sign-in (off unless the otp_login_enabled setting is on).
    Route::post('auth/otp/request', [OtpLoginController::class, 'request'])->middleware('throttle:auth');
    Route::post('auth/otp/verify', [OtpLoginController::class, 'verify'])->middleware('throttle:auth');
});
