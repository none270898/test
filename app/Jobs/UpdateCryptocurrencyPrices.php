<?php
// app/Jobs/UpdateCryptocurrencyPrices.php
namespace App\Jobs;

use App\Models\Cryptocurrency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateCryptocurrencyPrices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            // Pobierz wszystkie kryptowaluty z bazy
            $cryptocurrencies = Cryptocurrency::pluck('coingecko_id')->toArray();
            
            if (empty($cryptocurrencies)) {
                $this->fetchAllCryptocurrencies();
                return;
            }

            // Podziel na chunki po 250 (limit CoinGecko)
            $chunks = array_chunk($cryptocurrencies, 250);
            
            foreach ($chunks as $chunk) {
                $ids = implode(',', $chunk);
                
                $response = Http::get('https://api.coingecko.com/api/v3/simple/price', [
                    'ids' => $ids,
                    'vs_currencies' => 'usd,pln',
                    'include_market_cap' => 'true',
                    'include_24hr_change' => 'true',
                    'include_last_updated_at' => 'true'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $this->updatePrices($data);
                }
                
                // Rate limiting - CoinGecko allows ~50 calls/minute for free tier
                sleep(2);
            }

            Log::info('Cryptocurrency prices updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Error updating cryptocurrency prices: ' . $e->getMessage());
        }
    }

    private function fetchAllCryptocurrencies()
    {
        try {
            $response = Http::get('https://api.coingecko.com/api/v3/coins/markets', [
                'vs_currency' => 'usd',
                'order' => 'market_cap_desc',
                'per_page' => 250,
                'page' => 1,
                'sparkline' => false,
                'price_change_percentage' => '24h'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                foreach ($data as $coin) {
                    Cryptocurrency::updateOrCreate(
                        ['coingecko_id' => $coin['id']],
                        [
                            'symbol' => strtoupper($coin['symbol']),
                            'name' => $coin['name'],
                            'image' => $coin['image'],
                            'current_price_usd' => $coin['current_price'],
                            'market_cap' => $coin['market_cap'],
                            'market_cap_rank' => $coin['market_cap_rank'],
                            'price_change_24h' => $coin['price_change_24h'],
                            'price_change_percentage_24h' => $coin['price_change_percentage_24h'],
                            'last_updated' => now(),
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching all cryptocurrencies: ' . $e->getMessage());
        }
    }

    private function updatePrices($data)
    {
        foreach ($data as $coingeckoId => $priceData) {
            Cryptocurrency::where('coingecko_id', $coingeckoId)->update([
                'current_price_usd' => $priceData['usd'] ?? null,
                'current_price_pln' => $priceData['pln'] ?? null,
                'market_cap' => $priceData['usd_market_cap'] ?? null,
                'price_change_24h' => $priceData['usd_24h_change'] ?? null,
                'price_change_percentage_24h' => $priceData['usd_24h_change'] ?? null,
                'last_updated' => now(),
            ]);
        }
    }
}
