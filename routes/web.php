<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Mrj\Foundation\Http\Controllers\DashboardLayoutController;

/*
| The admin dashboard. A project changes the page by creating
| resources/views/dashboard.blade.php, or turns this route off with
| 'foundation.routing.dashboard' => false and defines admin.dashboard itself.
*/

Route::middleware(config('foundation.routing.middleware'))
    ->domain(config('foundation.routing.domain'))
    ->prefix(config('foundation.routing.prefix'))
    ->name('admin.')
    ->group(function (): void {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::put('dashboard/layout', [DashboardLayoutController::class, 'update'])->name('dashboard.layout.update');
        Route::delete('dashboard/layout', [DashboardLayoutController::class, 'destroy'])->name('dashboard.layout.reset');
    });
