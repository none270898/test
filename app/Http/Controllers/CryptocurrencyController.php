<?php
namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Services\CoinGeckoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CryptocurrencyController extends Controller
{
    public function __construct(private CoinGeckoService $coinGeckoService)
    {
    }

    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = min($request->get('per_page', 50), 100);

        $query = Cryptocurrency::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        $cryptocurrencies = $query
            ->orderBy('current_price_usd', 'desc')
            ->paginate($perPage);

        return response()->json($cryptocurrencies);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Search in local database first
        $localResults = Cryptocurrency::where('name', 'like', "%{$query}%")
            ->orWhere('symbol', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'symbol', 'image', 'current_price_pln']);

        // If we have enough local results, return them
        if ($localResults->count() >= 5) {
            return response()->json($localResults);
        }

        // Otherwise, search CoinGecko and update local database
        $apiResults = $this->coinGeckoService->searchCryptocurrencies($query);
        
        foreach (array_slice($apiResults, 0, 5) as $coin) {
            Cryptocurrency::updateOrCreate(
                ['coingecko_id' => $coin['id']],
                [
                    'symbol' => strtoupper($coin['symbol']),
                    'name' => $coin['name'],
                    'image' => $coin['large'] ?? $coin['thumb'],
                ]
            );
        }

        // Get updated results
        $updatedResults = Cryptocurrency::where('name', 'like', "%{$query}%")
            ->orWhere('symbol', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'symbol', 'image', 'current_price_pln']);

        return response()->json($updatedResults);
    }
}