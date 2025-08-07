<?php
// app/Services/CoinGeckoService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CoinGeckoService
{
    private string $baseUrl = 'https://api.coingecko.com/api/v3';
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.coingecko.api_key');
    }

    /**
     * Get all cryptocurrencies from CoinGecko
     */
    public function getAllCryptocurrencies(int $perPage = 250, int $page = 1): array
    {
        $cacheKey = "coingecko_coins_list_{$page}_{$perPage}";
        
        return Cache::remember($cacheKey, 3600, function () use ($perPage, $page) {
            try {
                $response = Http::timeout(30)->get($this->baseUrl . '/coins/markets', [
                    'vs_currency' => 'usd',
                    'order' => 'market_cap_desc',
                    'per_page' => $perPage,
                    'page' => $page,
                    'sparkline' => false,
                    'price_change_percentage' => '24h',
                    'x_cg_demo_api_key' => $this->apiKey,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('CoinGecko API error', ['response' => $response->body()]);
                return [];
            } catch (\Exception $e) {
                Log::error('CoinGecko API exception', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get current prices for specific cryptocurrencies
     */
    public function getCurrentPrices(array $coinIds): array
    {
        if (empty($coinIds)) {
            return [];
        }

        $cacheKey = 'coingecko_prices_' . md5(implode(',', $coinIds));
        
        return Cache::remember($cacheKey, 300, function () use ($coinIds) { // 5 min cache
            try {
                $response = Http::timeout(30)->get($this->baseUrl . '/simple/price', [
                    'ids' => implode(',', $coinIds),
                    'vs_currencies' => 'usd,pln',
                    'include_24hr_change' => true,
                    'include_last_updated_at' => true,
                    'x_cg_demo_api_key' => $this->apiKey,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('CoinGecko prices API error', ['response' => $response->body()]);
                return [];
            } catch (\Exception $e) {
                Log::error('CoinGecko prices API exception', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Search cryptocurrencies
     */
    public function searchCryptocurrencies(string $query): array
    {
        $cacheKey = 'coingecko_search_' . md5($query);
        
        return Cache::remember($cacheKey, 1800, function () use ($query) { // 30 min cache
            try {
                $response = Http::timeout(30)->get($this->baseUrl . '/search', [
                    'query' => $query,
                    'x_cg_demo_api_key' => $this->apiKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['coins'] ?? [];
                }

                Log::error('CoinGecko search API error', ['response' => $response->body()]);
                return [];
            } catch (\Exception $e) {
                Log::error('CoinGecko search API exception', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get USD to PLN exchange rate
     */
    public function getUsdPlnRate(): float
    {
        return Cache::remember('usd_pln_rate', 1800, function () {
            try {
                $response = Http::timeout(30)->get($this->baseUrl . '/simple/price', [
                    'ids' => 'usd',
                    'vs_currencies' => 'pln',
                    'x_cg_demo_api_key' => $this->apiKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['usd']['pln'] ?? 4.0; // Fallback rate
                }

                // Fallback to NBP API if CoinGecko fails
                $nbpResponse = Http::timeout(10)->get('https://api.nbp.pl/api/exchangerates/rates/a/usd/');
                if ($nbpResponse->successful()) {
                    $nbpData = $nbpResponse->json();
                    return $nbpData['rates'][0]['mid'] ?? 4.0;
                }

                return 4.0; // Final fallback
            } catch (\Exception $e) {
                Log::error('USD/PLN rate fetch failed', ['error' => $e->getMessage()]);
                return 4.0;
            }
        });
    }
}