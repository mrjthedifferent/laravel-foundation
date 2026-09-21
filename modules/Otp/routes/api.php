<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Otp\Http\Controllers\OtpVerificationController;

Route::prefix('v1')->group(function (): void {
    Route::post('send-verification-code', [OtpVerificationController::class, 'sendVerificationCode'])->middleware('throttle:10,1');
    Route::post('verify-otp', [OtpVerificationController::class, 'verifyCode'])->middleware('throttle:10,1');
});
