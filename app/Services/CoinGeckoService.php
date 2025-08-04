<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CoinGeckoService
{
    private $baseUrl = 'https://api.coingecko.com/api/v3';
    private $rateLimitDelay = 2; // seconds between requests

    public function getAllCoins()
    {
        return Cache::remember('coingecko_all_coins', 3600, function () {
            try {
                $response = Http::get($this->baseUrl . '/coins/list');
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                return [];
            } catch (\Exception $e) {
                Log::error('CoinGecko getAllCoins error: ' . $e->getMessage());
                return [];
            }
        });
    }

    public function getMarketData($page = 1, $perPage = 250)
    {
        try {
            $response = Http::get($this->baseUrl . '/coins/markets', [
                'vs_currency' => 'usd',
                'order' => 'market_cap_desc',
                'per_page' => $perPage,
                'page' => $page,
                'sparkline' => false,
                'price_change_percentage' => '24h'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('CoinGecko getMarketData error: ' . $e->getMessage());
            return [];
        }
    }

    public function getPrices($ids, $vsCurrencies = ['usd', 'pln'])
    {
        try {
            $response = Http::get($this->baseUrl . '/simple/price', [
                'ids' => is_array($ids) ? implode(',', $ids) : $ids,
                'vs_currencies' => implode(',', $vsCurrencies),
                'include_market_cap' => 'true',
                'include_24hr_change' => 'true',
                'include_last_updated_at' => 'true'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('CoinGecko getPrices error: ' . $e->getMessage());
            return [];
        }
    }

    public function getExchangeRates()
    {
        return Cache::remember('coingecko_exchange_rates', 300, function () {
            try {
                $response = Http::get($this->baseUrl . '/exchange_rates');
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                return null;
            } catch (\Exception $e) {
                Log::error('CoinGecko getExchangeRates error: ' . $e->getMessage());
                return null;
            }
        });
    }

    public function getCoinHistory($coinId, $date)
    {
        try {
            $response = Http::get($this->baseUrl . "/coins/{$coinId}/history", [
                'date' => $date,
                'localization' => false
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('CoinGecko getCoinHistory error: ' . $e->getMessage());
            return null;
        }
    }
}