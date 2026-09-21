<?php

use Illuminate\Support\Facades\Route;
use Modules\ErrorReport\Http\Controllers\ErrorReportController;
use Modules\ErrorReport\Http\Controllers\ErrorReportSettingsController;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('error-reports/settings', [ErrorReportSettingsController::class, 'index'])
        ->name('error-reports.settings.index');
    Route::post('error-reports/settings', [ErrorReportSettingsController::class, 'update'])
        ->name('error-reports.settings.update');

    Route::post('error-reports/{errorReport}/resolve', [ErrorReportController::class, 'resolve'])
        ->name('error-reports.resolve');
    Route::resource('error-reports', ErrorReportController::class)
        ->only(['index', 'show', 'destroy'])
        ->names('error-reports');
});
