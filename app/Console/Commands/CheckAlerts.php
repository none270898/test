<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CheckPriceAlerts;

class CheckAlerts extends Command
{
    protected $signature = 'crypto:check-alerts';
    protected $description = 'Check and trigger price alerts';

    public function handle()
    {
        $this->info('Checking price alerts...');
        
        CheckPriceAlerts::dispatch();
        
        $this->info('Alert check completed!');
        
        return 0;
    }
}