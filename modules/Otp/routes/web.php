<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Otp\Http\Controllers\OtpWhitelistController;
use Modules\Otp\Http\Controllers\VerificationCodeHistoryController;

Route::middleware(config('foundation.routing.middleware'))
    ->domain(config('foundation.routing.domain'))
    ->prefix(config('foundation.routing.prefix'))
    ->name('admin.')
    ->group(function (): void {
        Route::resource('otp-whitelist', OtpWhitelistController::class);

        Route::get('otp/verification-history', [VerificationCodeHistoryController::class, 'index'])
            ->name('otp.history.index');
    });
