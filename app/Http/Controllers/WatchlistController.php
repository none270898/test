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
     * Get user's watchlist
     */
    public function index()
    {
        $user = Auth::user();

        // if ($user->watchlist()->count() === 0) {
        //     $this->sentimentService->initializeDefaultWatchlist($user);
        // }

        // Dodaj ID do odpowiedzi
        $watchlist = $user->watchlist()
            ->with('cryptocurrency')
            ->get();

        $watchlistSummary = $watchlist->map(function ($item) {
            $crypto = $item->cryptocurrency;
            $latestAnalysis = $crypto->getLatestTrendAnalysis();

            return [
                'id' => $item->id, // DODANE ID
                'cryptocurrency' => [
                    'id' => $crypto->id,
                    'name' => $crypto->name,
                    'symbol' => $crypto->symbol,
                    'image' => $crypto->image,
                    'current_price_pln' => $crypto->current_price_pln,
                    'price_change_24h' => $crypto->price_change_24h,
                ],
                'sentiment_avg' => $crypto->current_sentiment ?? 0,
                'mention_count' => $crypto->daily_mentions ?? 0,
                'trend_direction' => $latestAnalysis?->trend_direction ?? 'neutral',
                'confidence_score' => $latestAnalysis?->confidence_score ?? 0,
                'sentiment_change' => $crypto->sentiment_change_24h ?? 0,
                'emoji' => $latestAnalysis?->getTrendEmoji() ?? '➡️',
                'analysis_time' => $crypto->sentiment_updated_at?->diffForHumans() ?? 'Never',
                'notifications_enabled' => $item->notifications_enabled,
            ];
        });

        return response()->json([
            'watchlist' => $watchlistSummary,
            'total_count' => $watchlistSummary->count(),
        ]);
    }

    /**
     * Add cryptocurrency to watchlist
     */
    public function store(Request $request)
    {
        $request->validate([
            'cryptocurrency_id' => 'required|exists:cryptocurrencies,id',
            'notifications_enabled' => 'boolean',
        ]);

        $user = Auth::user();

        $watchlistItem = UserWatchlist::firstOrCreate([
            'user_id' => $user->id,
            'cryptocurrency_id' => $request->cryptocurrency_id,
        ], [
            'notifications_enabled' => $request->boolean('notifications_enabled', true),
        ]);

        return response()->json([
            'message' => 'Cryptocurrency added to watchlist',
            'watchlist_item' => $watchlistItem->load('cryptocurrency'),
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

        return response()->json([
            'message' => "{$cryptoName} removed from watchlist",
        ]);
    }

    /**
     * Bulk add cryptocurrencies to watchlist
     */
    public function bulkAdd(Request $request)
    {
        $request->validate([
            'cryptocurrency_ids' => 'required|array|min:1|max:20',
            'cryptocurrency_ids.*' => 'exists:cryptocurrencies,id',
            'notifications_enabled' => 'boolean',
        ]);

        $user = Auth::user();
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

        return response()->json([
            'message' => "Added {$addedCount} cryptocurrencies to watchlist",
            'added_count' => $addedCount,
        ]);
    }
}
