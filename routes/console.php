<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('crypto:update-prices')->everyFiveMinutes();
Schedule::command('crypto:sync-coins --pages=1')->daily();
Schedule::command('alerts:check')->everyMinute();