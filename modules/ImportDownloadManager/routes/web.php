<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ImportDownloadManager\Http\Controllers\DownloadImportManagerController;

Route::prefix('admin/download-import-manager')->middleware(['auth', 'verified'])->name('admin.')->group(function (): void {
    Route::get('/', [DownloadImportManagerController::class, 'index'])->name('download.import.manager.index');
    Route::delete('/{downloadImportManager}', [DownloadImportManagerController::class, 'destroy'])->name('download.import.manager.delete');
    Route::post('/status-update', [DownloadImportManagerController::class, 'statusUpdate'])->name('download.import.status.update');
    Route::get('/{downloadImportManager}/download', [DownloadImportManagerController::class, 'download'])->name('download.import.manager.download');
});
