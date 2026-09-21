<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('system:backup:cleanup')->daily()->at('02:00');
Schedule::command('clear:old-notification')->dailyAt('12:10');
