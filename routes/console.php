<?php

use Illuminate\Support\Facades\Schedule;

// Basic price updates every 5 minutes
Schedule::command('crypto:update-prices')->everyFiveMinutes();
Schedule::command('crypto:sync-coins --pages=10')->daily();
Schedule::command('alerts:check')->everyMinute();

// AI Sentiment Analysis Pipeline - INCREASED FREQUENCY
Schedule::command('sentiment:scrape')->everyThirtyMinutes(); // Every 30 minutes instead of hourly
Schedule::command('sentiment:analyze')->hourly(); // Every hour instead of every 2 hours

// Full pipeline twice daily for comprehensive analysis
Schedule::command('sentiment:pipeline --async')->twiceDaily(9, 21); // 9 AM and 9 PM