<?php
namespace App\Console\Commands;

use App\Models\Cryptocurrency;
use App\Services\CoinGeckoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateCryptocurrencyPrices extends Command
{
    protected $signature = 'crypto:update-prices';
    protected $description = 'Update cryptocurrency prices from CoinGecko';

    public function __construct(private CoinGeckoService $coinGeckoService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Updating cryptocurrency prices...');

        // Get all cryptocurrencies we have in database
        $cryptocurrencies = Cryptocurrency::all();

        if ($cryptocurrencies->isEmpty()) {
            $this->warn('No cryptocurrencies in database. Run crypto:sync-coins first.');
            return self::FAILURE;
        }

        // Get CoinGecko IDs
        $coinIds = $cryptocurrencies->pluck('coingecko_id')->toArray();

        // Split into chunks of 100 (CoinGecko limit)
        $chunks = array_chunk($coinIds, 100);
        $usdPlnRate = $this->coinGeckoService->getUsdPlnRate();

        $this->info("USD/PLN rate: {$usdPlnRate}");

        foreach ($chunks as $index => $chunk) {
            $this->info("Processing chunk " . ($index + 1) . " of " . count($chunks));
            
            $prices = $this->coinGeckoService->getCurrentPrices($chunk);

            foreach ($prices as $coinId => $priceData) {
                $crypto = $cryptocurrencies->where('coingecko_id', $coinId)->first();
                
                if (!$crypto) continue;

                $usdPrice = $priceData['usd'] ?? 0;
                $plnPrice = $priceData['pln'] ?? ($usdPrice * $usdPlnRate);
                $change24h = $priceData['usd_24h_change'] ?? 0;

                $crypto->update([
                    'current_price_usd' => $usdPrice,
                    'current_price_pln' => $plnPrice,
                    'price_change_24h' => $change24h,
                    'last_updated' => now(),
                ]);

                $this->line("Updated {$crypto->symbol}: {$plnPrice} PLN");
            }

            // Sleep to avoid rate limiting
            if ($index < count($chunks) - 1) {
                sleep(1);
            }
        }

        $this->info('Cryptocurrency prices updated successfully!');
        return self::SUCCESS;
    }
}