<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\TrendAnalysis;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        
        $portfolioValue = $user->total_portfolio_value;
        $portfolioCount = $user->portfolios()->count();
        $alertsCount = $user->priceAlerts()->where('is_active', true)->count();
        
        // Top cryptocurrencies
        $topCryptos = Cryptocurrency::orderBy('market_cap_rank')
            ->limit(10)
            ->get();
        
        // Latest trend analyses (premium feature)
        $trendAnalyses = collect();
        if ($user->isPremium()) {
            $trendAnalyses = TrendAnalysis::with('cryptocurrency')
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('dashboard', compact(
            'portfolioValue', 
            'portfolioCount', 
            'alertsCount', 
            'topCryptos', 
            'trendAnalyses'
        ));
    }
}
