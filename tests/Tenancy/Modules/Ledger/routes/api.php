<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('ledger', fn () => ['ledger' => true])->name('ledger.index');
