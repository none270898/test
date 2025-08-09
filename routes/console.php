<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('crypto:update-prices')->everyFiveMinutes();
Schedule::command('crypto:sync-coins --pages=1')->daily();
Schedule::command('alerts:check')->everyMinute();

// AI Sentiment Analysis Pipeline
Schedule::command('sentiment:scrape')->hourly();
Schedule::command('sentiment:analyze')->everyTwoHours();
Schedule::command('sentiment:pipeline --async')->twiceDaily(9, 21); // 9 AM and 9 PM