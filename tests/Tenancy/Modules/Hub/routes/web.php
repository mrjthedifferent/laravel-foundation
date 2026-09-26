<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('hub', fn () => 'hub')->name('hub.index');
