<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cryptocurrency;
use Illuminate\Http\Request;

class CryptocurrencyController extends Controller
{
    public function index(Request $request)
    {
        $query = Cryptocurrency::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        $cryptocurrencies = $query->orderBy('market_cap_rank')
            ->paginate(50);

        return response()->json($cryptocurrencies);
    }

    public function show(Cryptocurrency $cryptocurrency)
    {
        return response()->json($cryptocurrency);
    }

    public function prices()
    {
        $prices = Cryptocurrency::select('id', 'symbol', 'current_price_pln', 'price_change_percentage_24h')
            ->get()
            ->keyBy('id');

        return response()->json($prices);
    }
}