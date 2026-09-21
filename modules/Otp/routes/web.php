<?php

use Illuminate\Support\Facades\Route;
use Modules\Otp\Http\Controllers\OtpWhitelistController;
use Modules\Otp\Http\Controllers\VerificationCodeHistoryController;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('otp-whitelist', OtpWhitelistController::class);

    Route::get('otp/verification-history', [VerificationCodeHistoryController::class, 'index'])
        ->name('otp.history.index');
});
