<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Mrj\Foundation\Http\Controllers\SyncController;

/*
 * Offline sync. Loaded only when foundation.offline_sync.handlers is not empty.
 */
Route::prefix(config('foundation.routing.api_prefix'))
    ->middleware(['auth:sanctum', 'throttle:api'])
    ->group(function (): void {
        Route::get('sync/pull', [SyncController::class, 'pull'])->name('foundation.sync.pull');
        Route::post('sync/push', [SyncController::class, 'push'])
            ->middleware('idempotent:required')
            ->name('foundation.sync.push');
    });
