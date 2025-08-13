<?php

namespace App\Console\Commands;

use App\Jobs\CheckSentimentAlerts;
use Illuminate\Console\Command;

class CheckSentimentAlertsCommand extends Command
{
    protected $signature = 'alerts:check-sentiment {--sync : Run synchronously}';
    protected $description = 'Check and trigger sentiment alerts for premium users';

    public function handle(): int
    {
        $this->info('🤖 Checking sentiment alerts...');
        
        $sync = $this->option('sync');
        
        if ($sync) {
            $this->line('Running synchronously...');
            CheckSentimentAlerts::dispatchNow();
        } else {
            $this->line('Dispatching to queue...');
            CheckSentimentAlerts::dispatch();
        }
        
        $this->info('✅ Sentiment alerts check initiated!');
        $this->info('Check storage/logs/laravel.log for details.');
        
        return self::SUCCESS;
    }
}