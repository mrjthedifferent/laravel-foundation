<?php

use Illuminate\Support\Facades\Route;
use Modules\BackupCleanup\Http\Controllers\BackupController;

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

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
    Route::post('backups/cleanup', [BackupController::class, 'cleanup'])->name('backups.cleanup');
    Route::get('backups/{filename}/download', [BackupController::class, 'download'])
        ->name('backups.download')
        ->where('filename', '.+');
    Route::delete('backups/{filename}', [BackupController::class, 'destroy'])
        ->name('backups.destroy')
        ->where('filename', '.+');
});
