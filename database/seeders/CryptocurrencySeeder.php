<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cryptocurrency;
use App\Services\CoinGeckoService;

class CryptocurrencySeeder extends Seeder
{
    public function run()
    {
        $coinGeckoService = new CoinGeckoService();
        
        // Najpopularniejsze kryptowaluty
        $popularCoins = [
            ['id' => 'bitcoin', 'symbol' => 'BTC', 'name' => 'Bitcoin'],
            ['id' => 'ethereum', 'symbol' => 'ETH', 'name' => 'Ethereum'],
            ['id' => 'cardano', 'symbol' => 'ADA', 'name' => 'Cardano'],
            ['id' => 'polkadot', 'symbol' => 'DOT', 'name' => 'Polkadot'],
            ['id' => 'chainlink', 'symbol' => 'LINK', 'name' => 'Chainlink'],
            ['id' => 'solana', 'symbol' => 'SOL', 'name' => 'Solana'],
            ['id' => 'avalanche-2', 'symbol' => 'AVAX', 'name' => 'Avalanche'],
            ['id' => 'polygon', 'symbol' => 'MATIC', 'name' => 'Polygon'],
        ];

        foreach ($popularCoins as $coin) {
            Cryptocurrency::updateOrCreate(
                ['coingecko_id' => $coin['id']],
                [
                    'symbol' => $coin['symbol'],
                    'name' => $coin['name'],
                    'market_cap_rank' => array_search($coin, $popularCoins) + 1,
                ]
            );
        }

        // Pobierz aktualne dane z CoinGecko
        $marketData = $coinGeckoService->getMarketData(1, 100);
        
        foreach ($marketData as $coin) {
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
}