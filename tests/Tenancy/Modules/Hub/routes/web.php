<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('hub', fn () => 'hub')->name('hub.index');

// The shape foundation:make-module generates, which reads the routing domain itself.
Route::middleware(config('foundation.routing.middleware'))
    ->domain(config('foundation.routing.domain'))
    ->prefix(config('foundation.routing.prefix'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('hubs', fn () => 'hubs')->name('hubs.index');
    });
