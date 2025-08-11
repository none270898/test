<?php
// app/Http/Middleware/CheckLimits.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLimits
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        switch ($feature) {
            case 'portfolio':
                if (!$user->isPremium() && $user->portfolioHoldings()->count() >= 10) {
                    return response()->json([
                        'error' => 'Portfolio limit reached',
                        'message' => 'Darmowy plan pozwala na śledzenie maksymalnie 10 kryptowalut. Upgrade do Premium aby dodać więcej.',
                        'upgrade_required' => true,
                        'current_limit' => 10,
                        'premium_feature' => 'Nieograniczone portfolio'
                    ], 403);
                }
                break;
                
            case 'alerts':
                if (!$user->isPremium() && $user->priceAlerts()->where('is_active', true)->count() >= 5) {
                    return response()->json([
                        'error' => 'Alerts limit reached',
                        'message' => 'Darmowy plan pozwala na maksymalnie 5 aktywnych alertów. Upgrade do Premium dla nieograniczonych alertów.',
                        'upgrade_required' => true,
                        'current_limit' => 5,
                        'premium_feature' => 'Nieograniczone alerty + sentiment alerts'
                    ], 403);
                }
                break;
                
            case 'watchlist':
                if (!$user->isPremium() && $user->watchlist()->count() >= 15) {
                    return response()->json([
                        'error' => 'Watchlist limit reached',
                        'message' => 'Darmowy plan pozwala na maksymalnie 15 pozycji w watchlist. Upgrade do Premium dla nieograniczonej watchlist z AI insights.',
                        'upgrade_required' => true,
                        'current_limit' => 15,
                        'premium_feature' => 'Nieograniczona watchlist + sentiment tracking'
                    ], 403);
                }
                break;
        }

        return $next($request);
    }
}