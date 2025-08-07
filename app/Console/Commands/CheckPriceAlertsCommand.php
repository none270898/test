<?php
namespace App\Console\Commands;

use App\Jobs\CheckPriceAlerts;
use Illuminate\Console\Command;

class CheckPriceAlertsCommand extends Command
{
    protected $signature = 'alerts:check';
    protected $description = 'Check and trigger price alerts';

    public function handle(): int
    {
        $this->info('Dispatching price alerts check job...');
        
        CheckPriceAlerts::dispatch();
        
        $this->info('Price alerts check job dispatched successfully');
        return self::SUCCESS;
    }
}