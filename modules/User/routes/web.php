<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\AccountDeletionController;
use Modules\User\Http\Controllers\DeletionRequestController;
use Modules\User\Http\Controllers\DocumentController;
use Modules\User\Http\Controllers\GlobalSearchController;
use Modules\User\Http\Controllers\ImpersonationController;
use Modules\User\Http\Controllers\ProfileController;
use Modules\User\Http\Controllers\TwoFactorController;
use Modules\User\Http\Controllers\UserController;
use Modules\User\Http\Middleware\RequireRecentPasswordConfirmation;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public: anyone deletes their own account without the app (app stores require this link).
if (config('foundation.routing.account_deletion_page', true)) {
    Route::get('delete-account', [AccountDeletionController::class, 'show'])->name('account.delete');
    Route::post('delete-account', [AccountDeletionController::class, 'destroy'])
        ->middleware('throttle:auth')
        ->name('account.delete.destroy');
}

Route::middleware(config('foundation.routing.middleware'))
    ->domain(config('foundation.routing.domain'))
    ->prefix(config('foundation.routing.prefix'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('/global-search', [GlobalSearchController::class, 'search'])->name('global-search');

        // Two-factor authentication (foundation.two_factor.enabled)
        Route::post('/profile/two-factor', [TwoFactorController::class, 'store'])
            ->middleware(RequireRecentPasswordConfirmation::class)
            ->name('profile.two-factor.enable');
        Route::post('/profile/two-factor/confirm', [TwoFactorController::class, 'confirm'])
            ->middleware('throttle:auth')
            ->name('profile.two-factor.confirm');
        Route::post('/profile/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])
            ->middleware(RequireRecentPasswordConfirmation::class)
            ->name('profile.two-factor.recovery-codes');
        Route::delete('/profile/two-factor', [TwoFactorController::class, 'destroy'])
            ->middleware(RequireRecentPasswordConfirmation::class)
            ->name('profile.two-factor.disable');
        Route::delete('users/{user}/two-factor', [TwoFactorController::class, 'reset'])->name('users.two-factor.reset');

        Route::resource('users', UserController::class);
        Route::get('reset/password/{user}', [UserController::class, 'resetPassword'])->name('user.password.reset');
        Route::post('users/{user}/verify-email', [UserController::class, 'verifyEmail'])->name('users.verify.email');
        Route::post('users/{user}/verify-phone', [UserController::class, 'verifyPhone'])->name('users.verify.phone');
        Route::get('users-export', [UserController::class, 'export'])->name('users.export');
        Route::get('users-bulk-upload', [UserController::class, 'bulkUploadPage'])->name('users.bulk.create');
        Route::get('users-bulk-upload/sample', [UserController::class, 'bulkUploadSample'])->name('users.bulk.sample');
        Route::post('users-bulk-upload', [UserController::class, 'bulkUpload'])->name('users.bulk');
        Route::post('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');

        // User account management route (reset/delete)
        Route::post('users/{user}/manage-account', [UserController::class, 'manageAccount'])->name('users.account.manage');

        // Account deletion requests (Review Account Deletion; deleting now needs Anonymize Account)
        Route::get('deletion-requests', [DeletionRequestController::class, 'index'])->name('deletion-requests.index');
        Route::post('deletion-requests/{deletionRequest}/approve', [DeletionRequestController::class, 'approve'])->name('deletion-requests.approve');
        Route::post('deletion-requests/{deletionRequest}/reject', [DeletionRequestController::class, 'reject'])->name('deletion-requests.reject');
        Route::post('deletion-requests/{deletionRequest}/anonymize', [DeletionRequestController::class, 'anonymize'])
            ->middleware(RequireRecentPasswordConfirmation::class)
            ->name('deletion-requests.anonymize');

        // Impersonation (Super Admin only — see UserPolicy::impersonate)
        Route::post('users/{user}/impersonate', [ImpersonationController::class, 'store'])
            ->middleware(RequireRecentPasswordConfirmation::class)
            ->name('users.impersonate');
        Route::post('impersonation/leave', [ImpersonationController::class, 'destroy'])->name('impersonation.leave');

        // Document management
        Route::post('users/{user}/documents', [DocumentController::class, 'store'])->name('users.documents.store');
        Route::delete('users/{user}/documents/{documentId}', [DocumentController::class, 'destroy'])->name('users.documents.destroy');
    });

require __DIR__.'/auth.php';
