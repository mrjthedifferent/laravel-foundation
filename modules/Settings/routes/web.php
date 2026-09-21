<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\NotificationSettingsController;
use Modules\Settings\Http\Controllers\SettingsController;
use Modules\Settings\Http\Controllers\SpecialSettingsController;
use Modules\Settings\Http\Controllers\ThemeSettingsController;

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

Route::middleware(config('foundation.routing.middleware'))
    ->domain(config('foundation.routing.domain'))
    ->prefix(config('foundation.routing.prefix'))
    ->name('admin.')
    ->group(function (): void {
        // Regular settings routes
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'store'])->name('settings.store');

        // New routes for managing settings entries
        Route::get('settings/manage', [SettingsController::class, 'manage'])->name('settings.manage');
        Route::post('settings/sync', [SettingsController::class, 'syncSettings'])->name('settings.sync');
        Route::get('settings/create', [SettingsController::class, 'create'])->name('settings.create');
        Route::post('settings/create', [SettingsController::class, 'storeNew'])->name('settings.store_new');

        // Import and export routes
        Route::get('settings/export', [SettingsController::class, 'export'])->name('settings.export');
        Route::get('settings/import', [SettingsController::class, 'importForm'])->name('settings.import_form');
        Route::post('settings/import', [SettingsController::class, 'import'])->name('settings.import');

        // Bulk operations routes — must be registered before settings/{setting} to avoid wildcard conflict
        Route::put('settings/bulk-update', [SettingsController::class, 'bulkUpdate'])->name('settings.bulk_update');
        Route::delete('settings/bulk-delete', [SettingsController::class, 'bulkDelete'])->name('settings.bulk_delete');

        // Resource-style routes with {setting} wildcard — must come after fixed-path routes
        Route::get('settings/{setting}/edit', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings/{setting}', [SettingsController::class, 'update'])->name('settings.update');
        Route::delete('settings/{setting}', [SettingsController::class, 'destroy'])->name('settings.destroy');

        // Special settings routes
        Route::prefix('settings/special')->name('settings.special.')->group(function (): void {
            // Privacy Policy routes
            Route::get('privacy-policy', [SpecialSettingsController::class, 'privacyPolicy'])->name('privacy_policy');
            Route::post('privacy-policy', [SpecialSettingsController::class, 'updatePrivacyPolicy'])->name('update_privacy_policy');

            // Terms & Conditions routes
            Route::get('terms-conditions', [SpecialSettingsController::class, 'termsConditions'])->name('terms_conditions');
            Route::post('terms-conditions', [SpecialSettingsController::class, 'updateTermsConditions'])->name('update_terms_conditions');

            // SMS Gateways routes
            Route::get('sms-gateways', [SpecialSettingsController::class, 'smsGateways'])->name('sms_gateways');
            Route::post('sms-gateways', [SpecialSettingsController::class, 'updateSmsGateways'])->name('update_sms_gateways');
            Route::post('test-sms', [SpecialSettingsController::class, 'sendTestSMS'])->name('send_test_sms');

            // Email Mailers routes
            Route::get('email-mailers', [SpecialSettingsController::class, 'emailMailers'])->name('email_mailers');
            Route::post('email-mailers', [SpecialSettingsController::class, 'updateEmailMailers'])->name('update_email_mailers');
            Route::post('test-email', [SpecialSettingsController::class, 'sendTestEmail'])->name('send_test_email');

            // Notification Settings (per-channel on/off matrix)
            Route::get('notifications', [NotificationSettingsController::class, 'show'])->name('notifications');
            Route::post('notifications', [NotificationSettingsController::class, 'update'])->name('update_notifications');
            Route::redirect('email-notifications', '/'.config('foundation.routing.prefix').'/settings/special/notifications')->name('notifications.legacy-redirect');

            // Firebase routes
            Route::get('firebase', [SpecialSettingsController::class, 'firebase'])->name('firebase');
            Route::post('firebase', [SpecialSettingsController::class, 'updateFirebase'])->name('update_firebase');
            Route::post('test-firebase', [SpecialSettingsController::class, 'testFirebaseConnection'])->name('test_firebase');

            // Social Auth routes
            Route::get('social-auth', [SpecialSettingsController::class, 'socialAuth'])->name('social_auth');
            Route::post('social-auth', [SpecialSettingsController::class, 'updateSocialAuth'])->name('update_social_auth');
            Route::post('test-google-auth', [SpecialSettingsController::class, 'testGoogleAuth'])->name('test_google_auth');
            Route::post('test-github-auth', [SpecialSettingsController::class, 'testGithubAuth'])->name('test_github_auth');
            Route::post('test-apple-auth', [SpecialSettingsController::class, 'testAppleAuth'])->name('test_apple_auth');

            // Theme settings routes
            Route::get('theme', [ThemeSettingsController::class, 'show'])->name('theme');
            Route::post('theme', [ThemeSettingsController::class, 'update'])->name('update_theme');

        });
    });
