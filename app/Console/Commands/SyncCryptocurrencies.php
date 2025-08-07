<?php
namespace App\Console\Commands;

use App\Models\Cryptocurrency;
use App\Services\CoinGeckoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCryptocurrencies extends Command
{
    protected $signature = 'crypto:sync-coins {--pages=2 : Number of pages to fetch}';
    protected $description = 'Sync cryptocurrency data from CoinGecko';

    public function __construct(private CoinGeckoService $coinGeckoService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Syncing cryptocurrencies from CoinGecko...');

        $pages = (int) $this->option('pages');
        $totalCoins = 0;

        for ($page = 1; $page <= $pages; $page++) {
            $this->info("Fetching page {$page}...");
            
            $coins = $this->coinGeckoService->getAllCryptocurrencies(250, $page);

            if (empty($coins)) {
                $this->error("No data received for page {$page}");
                continue;
            }

            foreach ($coins as $coin) {
                try {
                    $usdPrice = $coin['current_price'] ?? 0;
                    $change24h = $coin['price_change_percentage_24h'] ?? 0;
                    
                    // Calculate PLN price
                    $usdPlnRate = $this->coinGeckoService->getUsdPlnRate();
                    $plnPrice = $usdPrice * $usdPlnRate;

                    Cryptocurrency::updateOrCreate(
                        ['coingecko_id' => $coin['id']],
                        [
                            'symbol' => strtoupper($coin['symbol']),
                            'name' => $coin['name'],
                            'image' => $coin['image'],
                            'current_price_usd' => $usdPrice,
                            'current_price_pln' => $plnPrice,
                            'price_change_24h' => $change24h,
                            'last_updated' => now(),
                        ]
                    );

                    $totalCoins++;
                    $this->line("Synced: {$coin['name']} ({$coin['symbol']})");
                    
                } catch (\Exception $e) {
                    $this->error("Error syncing {$coin['id']}: " . $e->getMessage());
                    Log::error("Cryptocurrency sync error", [
                        'coin' => $coin['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Sleep to avoid rate limiting
            if ($page < $pages) {
                $this->info("Waiting 2 seconds to avoid rate limiting...");
                sleep(2);
            }
        }

        $this->info("Successfully synced {$totalCoins} cryptocurrencies!");
        return self::SUCCESS;
    }
}