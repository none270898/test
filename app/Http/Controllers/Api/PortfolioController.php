<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $portfolios = auth()->user()->portfolios()
            ->with('cryptocurrency')
            ->get()
            ->map(function($portfolio) {
                return [
                    'id' => $portfolio->id,
                    'cryptocurrency' => $portfolio->cryptocurrency,
                    'amount' => $portfolio->amount,
                    'average_buy_price' => $portfolio->average_buy_price,
                    'current_value' => $portfolio->current_value,
                    'profit_loss' => $portfolio->profit_loss,
                    'profit_loss_percentage' => $portfolio->profit_loss_percentage,
                ];
            });

        return response()->json($portfolios);
    }

    public function summary()
    {
        $user = auth()->user();
        $portfolios = $user->portfolios()->with('cryptocurrency')->get();
        
        $totalValue = $portfolios->sum('current_value');
        $totalInvested = $portfolios->sum(function($p) {
            return $p->amount * ($p->average_buy_price ?? 0);
        });
        
        $totalProfitLoss = $totalValue - $totalInvested;
        $totalProfitLossPercentage = $totalInvested > 0 ? ($totalProfitLoss / $totalInvested) * 100 : 0;

        $allocation = $portfolios->map(function($portfolio) use ($totalValue) {
            return [
                'cryptocurrency' => $portfolio->cryptocurrency->symbol,
                'percentage' => $totalValue > 0 ? ($portfolio->current_value / $totalValue) * 100 : 0,
                'value' => $portfolio->current_value,
            ];
        })->sortByDesc('percentage')->values();

        return response()->json([
            'total_value' => $totalValue,
            'total_invested' => $totalInvested,
            'total_profit_loss' => $totalProfitLoss,
            'total_profit_loss_percentage' => $totalProfitLossPercentage,
            'allocation' => $allocation,
        ]);
    }
}