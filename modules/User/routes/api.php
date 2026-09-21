<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Api\UserController;
use Modules\User\Http\Controllers\Api\UserDocumentController;

/*
 * Registration is closed: accounts are created by an administrator, so there is
 * no public sign-up, social sign-up or OTP auto-login endpoint.
 */

Route::prefix('v1')->group(function () {
    Route::post('login', [UserController::class, 'login'])->middleware('throttle:auth');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('profile', [UserController::class, 'me']);
        Route::patch('profile', [UserController::class, 'update']);
        Route::post('logout', [UserController::class, 'logout']);
        Route::post('change-password', [UserController::class, 'changePassword']);
        Route::post('manage-account', [UserController::class, 'manageAccount']);

        Route::get('user/documents', [UserDocumentController::class, 'index']);
        Route::post('user/documents', [UserDocumentController::class, 'store']);
    });
});
