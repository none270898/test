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
            'isPremium' => $premium_for_only_test_purpose = 1 ,
            'premium' => $user->premium,
            'premium_expires_at' => $user->premium_expires_at,
            'alerts_enabled' => $user->alerts_enabled,
            'email_notifications' => $user->email_notifications,
            
            // Limits and usage
            'limits' => [
                'portfolio' => [
                    'limit' => $premium_for_only_test_purpose = 1  ? null : 10,
                    'current' => $portfolioCount,
                    'remaining' => $premium_for_only_test_purpose = 1  ? null : max(0, 10 - $portfolioCount),
                    'unlimited' => $premium_for_only_test_purpose = 1 
                ],
                'alerts' => [
                    'limit' => $premium_for_only_test_purpose = 1  ? null : 5,
                    'current' => $activeAlertsCount,
                    'remaining' => $premium_for_only_test_purpose = 1  ? null : max(0, 5 - $activeAlertsCount),
                    'unlimited' => $premium_for_only_test_purpose = 1 
                ],
                'watchlist' => [
                    'limit' => $premium_for_only_test_purpose = 1  ? null : 15,
                    'current' => $watchlistCount,
                    'remaining' => $premium_for_only_test_purpose = 1  ? null : max(0, 15 - $watchlistCount),
                    'unlimited' => $premium_for_only_test_purpose = 1 
                ]
            ],
            
            // Feature access
            'features' => [
                'sentiment_analysis' => $premium_for_only_test_purpose = 1 ,
                'unlimited_portfolio' => $premium_for_only_test_purpose = 1 ,
                'unlimited_alerts' => $premium_for_only_test_purpose = 1 ,
                'unlimited_watchlist' => $premium_for_only_test_purpose = 1 ,
                'push_notifications' => $premium_for_only_test_purpose = 1 ,
                'sentiment_alerts' => $premium_for_only_test_purpose = 1 ,
                'ai_insights' => $premium_for_only_test_purpose = 1 ,
                'export_data' => $premium_for_only_test_purpose = 1 
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