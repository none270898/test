<?php
// app/Http/Controllers/SentimentController.php

namespace App\Http\Controllers;

use App\Models\TrendAnalysis;
use App\Models\SentimentData;
use App\Services\SentimentAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SentimentController extends Controller
{
    public function __construct(private SentimentAnalysisService $sentimentService)
    {
    }

    /**
     * Get trend analysis dashboard data
     */
    public function dashboard()
    {
        // Check if user is premium
        if (!Auth::user()->isPremium()) {
            return response()->json([
                'error' => 'Premium subscription required'
            ], 403);
        }

        $user = Auth::user();
        
        // Get user's watchlist sentiment summary
        $watchlistSummary = $this->sentimentService->getWatchlistSummary($user);
        
        // Get overall trending for comparison
        $trendingCryptos = $this->sentimentService->getTrendingCryptos(10);
        
        $recentActivity = SentimentData::select(['source', 'created_at'])
            ->where('created_at', '>=', now()->subHours(6))
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->groupBy('source')
            ->map(function ($items) {
                return $items->count();
            });

        $totalAnalyses = TrendAnalysis::where('created_at', '>=', now()->subDay())->count();
        $totalMentions = SentimentData::where('created_at', '>=', now()->subDay())->count();
        
        $averageSentiment = SentimentData::where('created_at', '>=', now()->subDay())
            ->avg('sentiment_score') ?? 0;

        return response()->json([
            'watchlist_summary' => $watchlistSummary,
            'trending_comparison' => $trendingCryptos,
            'stats' => [
                'total_analyses' => $totalAnalyses,
                'total_mentions' => $totalMentions,
                'average_sentiment' => round($averageSentiment, 2),
                'recent_activity' => $recentActivity,
                'watchlist_count' => count($watchlistSummary),
            ]
        ]);
    }

    /**
     * Get detailed trend analysis for specific cryptocurrency
     */
    public function cryptoTrend(Request $request, string $coinGeckoId)
    {
        // Check if user is premium
        if (!Auth::user()->isPremium()) {
            return response()->json([
                'error' => 'Premium subscription required'
            ], 403);
        }

        $hours = $request->get('hours', 24);
        
        // Get recent trend analyses
        $analyses = TrendAnalysis::whereHas('cryptocurrency', function ($query) use ($coinGeckoId) {
                $query->where('coingecko_id', $coinGeckoId);
            })
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->with('cryptocurrency')
            ->get();

        // Get raw sentiment data
        $sentimentData = SentimentData::where('created_at', '>=', now()->subHours($hours))
            ->whereJsonContains('keywords', $coinGeckoId)
            ->orderBy('created_at', 'desc')
            ->select(['sentiment_score', 'source', 'created_at', 'content'])
            ->limit(100)
            ->get();

        // Calculate hourly sentiment trend
        $hourlyTrend = $sentimentData->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d H:00');
        })->map(function ($hourData) {
            return [
                'avg_sentiment' => round($hourData->avg('sentiment_score'), 2),
                'mention_count' => $hourData->count(),
                'sources' => $hourData->groupBy('source')->map->count(),
            ];
        })->sortKeys();

        return response()->json([
            'analyses' => $analyses,
            'sentiment_data' => $sentimentData->take(20), // Latest 20 for details
            'hourly_trend' => $hourlyTrend,
            'summary' => [
                'total_mentions' => $sentimentData->count(),
                'avg_sentiment' => round($sentimentData->avg('sentiment_score'), 2),
                'sentiment_distribution' => [
                    'positive' => $sentimentData->where('sentiment_score', '>', 0.1)->count(),
                    'neutral' => $sentimentData->whereBetween('sentiment_score', [-0.1, 0.1])->count(),
                    'negative' => $sentimentData->where('sentiment_score', '<', -0.1)->count(),
                ],
            ]
        ]);
    }

    /**
     * Get sentiment sources breakdown
     */
    public function sources()
    {
        // Check if user is premium
        if (!Auth::user()->isPremium()) {
            return response()->json([
                'error' => 'Premium subscription required'
            ], 403);
        }

        $timeframe = now()->subHours(24);
        
        $sourceStats = SentimentData::where('created_at', '>=', $timeframe)
            ->selectRaw('source, COUNT(*) as count, AVG(sentiment_score) as avg_sentiment')
            ->groupBy('source')
            ->orderBy('count', 'desc')
            ->get();

        $recentBySource = SentimentData::where('created_at', '>=', now()->subHours(6))
            ->selectRaw('source, DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour, COUNT(*) as count')
            ->groupBy('source', 'hour')
            ->orderBy('hour', 'desc')
            ->get()
            ->groupBy('source');

        return response()->json([
            'source_stats' => $sourceStats,
            'recent_activity' => $recentBySource,
        ]);
    }

    /**
     * Search sentiment mentions
     */
    public function search(Request $request)
    {
        // Check if user is premium
        if (!Auth::user()->isPremium()) {
            return response()->json([
                'error' => 'Premium subscription required'
            ], 403);
        }

        $query = $request->get('q', '');
        $source = $request->get('source');
        $sentiment = $request->get('sentiment'); // 'positive', 'negative', 'neutral'
        $hours = $request->get('hours', 24);

        $builder = SentimentData::where('created_at', '>=', now()->subHours($hours));

        if ($query) {
            $builder->where('content', 'like', "%{$query}%");
        }

        if ($source) {
            $builder->where('source', $source);
        }

        if ($sentiment) {
            match($sentiment) {
                'positive' => $builder->where('sentiment_score', '>', 0.1),
                'negative' => $builder->where('sentiment_score', '<', -0.1),
                'neutral' => $builder->whereBetween('sentiment_score', [-0.1, 0.1]),
            };
        }

        $results = $builder->orderBy('created_at', 'desc')
            ->select(['content', 'sentiment_score', 'source', 'keywords', 'created_at', 'source_url'])
            ->paginate(20);

        return response()->json($results);
    }
}