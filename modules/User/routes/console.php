<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('accounts:purge-deleted')->dailyAt('03:10')->withoutOverlapping();
