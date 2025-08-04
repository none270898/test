<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\UpdateCryptocurrencyPrices;

class UpdateCryptoPrices extends Command
{
    protected $signature = 'crypto:update-prices';
    protected $description = 'Update cryptocurrency prices from CoinGecko';

    public function handle()
    {
        $this->info('Dispatching cryptocurrency price update job...');
        
        UpdateCryptocurrencyPrices::dispatch();
        
        $this->info('Job dispatched successfully!');
        
        return 0;
    }
}