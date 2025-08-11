<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get current user status including premium and limits
     */
    public function status()
    {
        $user = Auth::user();
        
        $portfolioCount = $user->portfolioHoldings()->count();
        $activeAlertsCount = $user->priceAlerts()->where('is_active', true)->count();
        $watchlistCount = $user->watchlist()->count();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'isPremium' => $user->isPremium(),
            'premium' => $user->premium,
            'premium_expires_at' => $user->premium_expires_at,
            'alerts_enabled' => $user->alerts_enabled,
            'email_notifications' => $user->email_notifications,
            
            // Limits and usage
            'limits' => [
                'portfolio' => [
                    'limit' => $user->isPremium() ? null : 10,
                    'current' => $portfolioCount,
                    'remaining' => $user->isPremium() ? null : max(0, 10 - $portfolioCount),
                    'unlimited' => $user->isPremium()
                ],
                'alerts' => [
                    'limit' => $user->isPremium() ? null : 5,
                    'current' => $activeAlertsCount,
                    'remaining' => $user->isPremium() ? null : max(0, 5 - $activeAlertsCount),
                    'unlimited' => $user->isPremium()
                ],
                'watchlist' => [
                    'limit' => $user->isPremium() ? null : 15,
                    'current' => $watchlistCount,
                    'remaining' => $user->isPremium() ? null : max(0, 15 - $watchlistCount),
                    'unlimited' => $user->isPremium()
                ]
            ],
            
            // Feature access
            'features' => [
                'sentiment_analysis' => $user->isPremium(),
                'unlimited_portfolio' => $user->isPremium(),
                'unlimited_alerts' => $user->isPremium(),
                'unlimited_watchlist' => $user->isPremium(),
                'push_notifications' => $user->isPremium(),
                'sentiment_alerts' => $user->isPremium(),
                'ai_insights' => $user->isPremium(),
                'export_data' => $user->isPremium()
            ]
        ]);
    }
    
    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'alerts_enabled' => 'boolean',
            'email_notifications' => 'boolean',
        ]);
        
        $user = Auth::user();
        $user->update($request->only(['alerts_enabled', 'email_notifications']));
        
        return response()->json([
            'message' => 'Preferences updated successfully',
            'user' => $this->status()->getData()
        ]);
    }
}