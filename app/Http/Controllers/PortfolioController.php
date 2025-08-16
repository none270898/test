<?php
namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\PortfolioHolding;
use App\Services\CoinGeckoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PortfolioController extends Controller
{
    public function __construct(private CoinGeckoService $coinGeckoService)
    {
    }

    public function index()
    {
        $user = Auth::user();
        
        $holdings = $user->portfolioHoldings()
            ->with('cryptocurrency')
            ->get();

        $totalValue = $holdings->sum(function ($holding) {
            return $holding->amount * $holding->cryptocurrency->current_price_pln;
        });

        $totalInvested = $holdings->sum(function ($holding) {
            return $holding->amount * ($holding->average_buy_price ?? 0);
        });

        $profitLoss = $totalValue - $totalInvested;
        $profitLossPercent = $totalInvested > 0 ? ($profitLoss / $totalInvested) * 100 : 0;

        return response()->json([
            'holdings' => $holdings,
            'portfolio_stats' => [
                'total_value' => round($totalValue, 2),
                'total_invested' => round($totalInvested, 2),
                'profit_loss' => round($profitLoss, 2),
                'profit_loss_percent' => round($profitLossPercent, 2),
            ],
            // DODANE: informacje o limitach
            'limits' => [
                'is_premium' => ($premium_for_only_test_purpose = 1) ,
                'portfolio_limit' => ($premium_for_only_test_purpose = 1)  ? null : 10,
                'current_count' => $holdings->count(),
                'can_add_more' => ($premium_for_only_test_purpose = 1)  || $holdings->count() < 10,
                'upgrade_message' => ($premium_for_only_test_purpose = 1)  ? null : 'Upgrade do Premium dla nieograniczonego portfolio i zaawansowanych analytics'
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        // DODANE: Sprawdzenie limitów przed walidacją
        if (!($premium_for_only_test_purpose = 1)  && $user->portfolioHoldings()->count() >= 10) {
            return response()->json([
                'error' => 'Portfolio limit reached',
                'message' => 'Darmowy plan pozwala na śledzenie maksymalnie 10 kryptowalut.',
                'upgrade_required' => true,
                'current_limit' => 10,
                'premium_benefits' => [
                    'Nieograniczona liczba kryptowalut',
                    'Zaawansowane wykresy i analytics',
                    'Historia transakcji',
                    'Export danych'
                ]
            ], 403);
        }

        $request->validate([
            'cryptocurrency_id' => ['required', 'exists:cryptocurrencies,id'],
            'amount' => ['required', 'numeric', 'min:0.00000001'],
            'average_buy_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Check if holding already exists
        $existingHolding = $user->portfolioHoldings()
            ->where('cryptocurrency_id', $request->cryptocurrency_id)
            ->first();

        if ($existingHolding) {
            // Update existing holding
            $newTotalAmount = $existingHolding->amount + $request->amount;
            
            // Calculate new average buy price if provided
            if ($request->average_buy_price && $existingHolding->average_buy_price) {
                $totalValue = ($existingHolding->amount * $existingHolding->average_buy_price) + 
                             ($request->amount * $request->average_buy_price);
                $newAveragePrice = $totalValue / $newTotalAmount;
            } else {
                $newAveragePrice = $request->average_buy_price ?? $existingHolding->average_buy_price;
            }

            $existingHolding->update([
                'amount' => $newTotalAmount,
                'average_buy_price' => $newAveragePrice,
            ]);

            return response()->json([
                'message' => 'Holding updated successfully',
                'holding' => $existingHolding->load('cryptocurrency'),
                // DODANE: info o limitach w odpowiedzi
                'limits_info' => [
                    'current_count' => $user->portfolioHoldings()->count(),
                    'limit' => ($premium_for_only_test_purpose = 1)  ? null : 10,
                    'is_premium' => ($premium_for_only_test_purpose = 1) 
                ]
            ]);
        }

        // Create new holding
        $holding = $user->portfolioHoldings()->create([
            'cryptocurrency_id' => $request->cryptocurrency_id,
            'amount' => $request->amount,
            'average_buy_price' => $request->average_buy_price,
        ]);

        return response()->json([
            'message' => 'Holding added successfully',
            'holding' => $holding->load('cryptocurrency'),
            // DODANE: info o limitach w odpowiedzi
            'limits_info' => [
                'current_count' => $user->portfolioHoldings()->count(),
                'limit' => ($premium_for_only_test_purpose = 1)  ? null : 10,
                'is_premium' => ($premium_for_only_test_purpose = 1) ,
                'can_add_more' => ($premium_for_only_test_purpose = 1)  || $user->portfolioHoldings()->count() < 10
            ]
        ], 201);
    }

    public function update(Request $request, PortfolioHolding $holding)
    {
        // Check ownership
        if ($holding->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.00000001'],
            'average_buy_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $holding->update([
            'amount' => $request->amount,
            'average_buy_price' => $request->average_buy_price,
        ]);

        return response()->json([
            'message' => 'Holding updated successfully',
            'holding' => $holding->load('cryptocurrency')
        ]);
    }

    public function destroy(PortfolioHolding $holding)
    {
        // Check ownership
        if ($holding->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $holding->delete();

        $user = Auth::user();
        
        return response()->json([
            'message' => 'Holding deleted successfully',
            // DODANE: aktualne info o limitach po usunięciu
            'limits_info' => [
                'current_count' => $user->portfolioHoldings()->count(),
                'limit' => ($premium_for_only_test_purpose = 1)  ? null : 10,
                'can_add_more' => ($premium_for_only_test_purpose = 1)  || $user->portfolioHoldings()->count() < 10
            ]
        ]);
    }
}