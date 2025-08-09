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
    public function __construct(private SentimentAnalysisService $sentimentService)
    {
    }

    /**
     * Get user's watchlist
     */
    public function index()
    {
        $user = Auth::user();
        
        // If user has no watchlist, initialize with defaults
        if ($user->watchlist()->count() === 0) {
            $this->sentimentService->initializeDefaultWatchlist($user);
        }

        $watchlistSummary = $this->sentimentService->getWatchlistSummary($user);

        return response()->json([
            'watchlist' => $watchlistSummary,
            'total_count' => count($watchlistSummary),
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
        dd($request);
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
