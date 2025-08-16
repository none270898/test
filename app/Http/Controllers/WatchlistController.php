<?php
// app/Http/Controllers/WatchlistController.php

namespace App\Http\Controllers;

use App\Models\UserWatchlist;
use App\Models\Cryptocurrency;
use App\Services\SentimentAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    public function __construct(private SentimentAnalysisService $sentimentService) {}

    /**
     * Get user's watchlist with premium-based sentiment access
     */
    public function index()
{
    $user = Auth::user();

    $watchlist = $user->watchlist()
        ->with('cryptocurrency')
        ->get();

    // Get today's smart alerts count for free users
    $todayAlertsCount = 0;
    if (!($premium_for_only_test_purpose = 1) ) {
        $todayAlertsCount = \App\Models\SentimentAlert::where('user_id', $user->id)
            ->where('last_triggered_at', '>=', now()->startOfDay())
            ->count();
    }

    $watchlistSummary = $watchlist->map(function ($item) use ($user, $todayAlertsCount) {
        $crypto = $item->cryptocurrency;
        $latestAnalysis = $crypto->getLatestTrendAnalysis();

        $data = [
            'id' => $item->id,
            'cryptocurrency' => [
                'id' => $crypto->id,
                'name' => $crypto->name,
                'symbol' => $crypto->symbol,
                'image' => $crypto->image,
                'coingecko_id' => $crypto->coingecko_id,
                'current_price_pln' => $crypto->current_price_pln ?? 0,
                'current_price_usd' => $crypto->current_price_usd ?? 0,
                'price_change_24h' => $crypto->price_change_24h ?? 0,
            ],
            'notifications_enabled' => $item->notifications_enabled,
        ];

        // Sentiment data dla premium i free (ale z limitami)
        if (($premium_for_only_test_purpose = 1) ) {
            $data['sentiment_avg'] = $crypto->current_sentiment ?? 0;
            $data['mention_count'] = $crypto->daily_mentions ?? 0;
            $data['sentiment_change'] = $crypto->sentiment_change_24h ?? 0;
            $data['trending_score'] = $crypto->trending_score ?? 0;
            $data['trending_status'] = $crypto->getTrendingStatus();
            $data['trend_direction'] = $latestAnalysis?->trend_direction ?? 'neutral';
            $data['confidence_score'] = $latestAnalysis?->confidence_score ?? 0;
            $data['emoji'] = $crypto->getSentimentEmoji();
            $data['analysis_time'] = $crypto->sentiment_updated_at?->diffForHumans() ?? 'Never';
            $data['has_recent_data'] = $crypto->hasRecentSentimentData(24);
            $data['todays_analyses_count'] = $crypto->getTodayTrendAnalyses()->count();
            $data['sentiment_access'] = true;
            $data['smart_alerts_status'] = 'unlimited';
        } else {
            // Free users - limited sentiment data
            $data['sentiment_avg'] = $crypto->current_sentiment ?? 0;
            $data['mention_count'] = $crypto->daily_mentions ?? 0;
            $data['sentiment_change'] = $crypto->sentiment_change_24h ?? 0;
            $data['trending_score'] = null; // Hidden for free
            $data['trending_status'] = null;
            $data['trend_direction'] = $latestAnalysis?->trend_direction ?? 'neutral';
            $data['confidence_score'] = null; // Hidden for free
            $data['emoji'] = $crypto->getSentimentEmoji();
            $data['analysis_time'] = $crypto->sentiment_updated_at?->diffForHumans() ?? 'Never';
            $data['has_recent_data'] = $crypto->hasRecentSentimentData(24);
            $data['todays_analyses_count'] = 0;
            $data['sentiment_access'] = true; // Basic access
            $data['smart_alerts_status'] = $todayAlertsCount >= 1 ? 'limit_reached' : 'available';
            $data['today_alerts_count'] = $todayAlertsCount;
            $data['daily_limit'] = 1;
        }

        return $data;
    });

    return response()->json([
        'watchlist' => $watchlistSummary,
        'total_count' => $watchlistSummary->count(),
        'limits' => [
            'is_premium' => ($premium_for_only_test_purpose = 1) ,
            'watchlist_limit' => ($premium_for_only_test_purpose = 1)  ? null : 5, // Zmienione z 15 na 5
            'current_count' => $watchlistSummary->count(),
            'can_add_more' => ($premium_for_only_test_purpose = 1)  || $watchlistSummary->count() < 5,
            'sentiment_access' => true, // Wszyscy mają podstawowy dostęp
            'upgrade_message' => ($premium_for_only_test_purpose = 1)  ? null : 'Upgrade do Premium dla unlimited smart alerts'
        ],
        'premium_features' => [
            'unlimited_watchlist' => ($premium_for_only_test_purpose = 1) ,
            'sentiment_tracking' => true, // Wszyscy mają basic
            'unlimited_smart_alerts' => ($premium_for_only_test_purpose = 1) ,
            'trend_analysis' => ($premium_for_only_test_purpose = 1) ,
            'ai_insights' => ($premium_for_only_test_purpose = 1) 
        ],
        'smart_alerts_info' => [
            'is_premium' => ($premium_for_only_test_purpose = 1) ,
            'daily_limit' => ($premium_for_only_test_purpose = 1)  ? null : 1,
            'today_count' => $todayAlertsCount,
            'remaining_today' => ($premium_for_only_test_purpose = 1)  ? null : max(0, 1 - $todayAlertsCount),
            'status' => ($premium_for_only_test_purpose = 1)  ? 'unlimited' : ($todayAlertsCount >= 1 ? 'limit_reached' : 'available')
        ]
    ]);
}

    /**
     * Add cryptocurrency to watchlist with limits
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // DODANE: Sprawdzenie limitów przed walidacją
        if (!($premium_for_only_test_purpose = 1)  && $user->watchlist()->count() >= 5) {
            return response()->json([
                'error' => 'Watchlist limit reached',
                'message' => 'Darmowy plan pozwala na maksymalnie 5 pozycji w watchlist.',
                'upgrade_required' => true,
                'current_limit' => 5,
                'current_count' => $user->watchlist()->count(),
                'premium_benefits' => [
                    'Nieograniczona watchlist',
                    'AI sentiment tracking',
                    'Sentiment notifications',
                    'Trend analysis i prognozy',
                    'Zaawansowane insights'
                ]
            ], 403);
        }

        $request->validate([
            'cryptocurrency_id' => 'required|exists:cryptocurrencies,id',
            'notifications_enabled' => 'boolean',
        ]);

        $watchlistItem = UserWatchlist::firstOrCreate([
            'user_id' => $user->id,
            'cryptocurrency_id' => $request->cryptocurrency_id,
        ], [
            'notifications_enabled' => $request->boolean('notifications_enabled', true),
        ]);

        $newCount = $user->watchlist()->count();

        return response()->json([
            'message' => 'Cryptocurrency added to watchlist',
            'watchlist_item' => $watchlistItem->load('cryptocurrency'),
            // DODANE: aktualne info o limitach
            'limits_info' => [
                'current_count' => $newCount,
                'limit' => ($premium_for_only_test_purpose = 1)  ? null : 15,
                'can_add_more' => ($premium_for_only_test_purpose = 1)  || $newCount < 15,
                'is_premium' => ($premium_for_only_test_purpose = 1) 
            ]
        ], $watchlistItem->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Update watchlist item settings
     */
    public function update(Request $request, UserWatchlist $watchlist)
    {
        // Check ownership
        if ($watchlist->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'notifications_enabled' => 'required|boolean',
        ]);

        $watchlist->update([
            'notifications_enabled' => $request->notifications_enabled,
        ]);

        return response()->json([
            'message' => 'Watchlist settings updated',
            'watchlist_item' => $watchlist->load('cryptocurrency'),
        ]);
    }

    /**
     * Remove cryptocurrency from watchlist
     */
    public function destroy(UserWatchlist $watchlist)
    {
        // Check ownership
        if ($watchlist->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cryptoName = $watchlist->cryptocurrency->name;
        $watchlist->delete();

        $user = Auth::user();
        $newCount = $user->watchlist()->count();

        return response()->json([
            'message' => "{$cryptoName} removed from watchlist",
            // DODANE: aktualne info o limitach po usunięciu
            'limits_info' => [
                'current_count' => $newCount,
                'limit' => ($premium_for_only_test_purpose = 1)  ? null : 15,
                'can_add_more' => ($premium_for_only_test_purpose = 1)  || $newCount < 15
            ]
        ]);
    }

    /**
     * Bulk add cryptocurrencies to watchlist with limits
     */
    public function bulkAdd(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'cryptocurrency_ids' => 'required|array|min:1|max:20',
            'cryptocurrency_ids.*' => 'exists:cryptocurrencies,id',
            'notifications_enabled' => 'boolean',
        ]);

        $currentCount = $user->watchlist()->count();
        $requestedCount = count($request->cryptocurrency_ids);
        $newTotalCount = $currentCount + $requestedCount;

        // DODANE: Sprawdzenie limitów dla bulk add
        if (!($premium_for_only_test_purpose = 1)  && $newTotalCount > 15) {
            $availableSlots = max(0, 15 - $currentCount);
            
            return response()->json([
                'error' => 'Bulk add would exceed watchlist limit',
                'message' => "Możesz dodać maksymalnie {$availableSlots} pozycji (limit: 15, aktualne: {$currentCount})",
                'upgrade_required' => true,
                'current_limit' => 15,
                'current_count' => $currentCount,
                'requested_count' => $requestedCount,
                'available_slots' => $availableSlots
            ], 403);
        }

        $addedCount = 0;

        foreach ($request->cryptocurrency_ids as $cryptoId) {
            $watchlistItem = UserWatchlist::firstOrCreate([
                'user_id' => $user->id,
                'cryptocurrency_id' => $cryptoId,
            ], [
                'notifications_enabled' => $request->boolean('notifications_enabled', true),
            ]);

            if ($watchlistItem->wasRecentlyCreated) {
                $addedCount++;
            }
        }

        $finalCount = $user->watchlist()->count();

        return response()->json([
            'message' => "Added {$addedCount} cryptocurrencies to watchlist",
            'added_count' => $addedCount,
            'skipped_count' => $requestedCount - $addedCount,
            // DODANE: aktualne info o limitach
            'limits_info' => [
                'current_count' => $finalCount,
                'limit' => ($premium_for_only_test_purpose = 1)  ? null : 15,
                'can_add_more' => ($premium_for_only_test_purpose = 1)  || $finalCount < 15
            ]
        ]);
    }
}