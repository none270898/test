<?php
namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\TrendAnalysis;
use App\Services\SentimentAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscoveryController extends Controller
{
    public function __construct(private SentimentAnalysisService $sentimentService)
    {
    }

    /**
     * Get trending cryptocurrencies for discovery
     */
    public function trending(Request $request)
    {
        $limit = min($request->get('limit', 20), 50);
        $trending = $this->sentimentService->getTrendingCryptos($limit);

        // Mark which ones are already in user's watchlist
        $user = Auth::user();
        $watchlistIds = $user->watchlist()->pluck('cryptocurrency_id')->toArray();

        $trendingWithWatchlistStatus = array_map(function ($item) use ($watchlistIds) {
            $item['is_watchlisted'] = in_array($item['id'], $watchlistIds);
            return $item;
        }, $trending);

        return response()->json([
            'trending' => $trendingWithWatchlistStatus,
            'total_count' => count($trendingWithWatchlistStatus),
            'updated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get sentiment history for a cryptocurrency
     */
    public function history(Request $request, string $coinGeckoId)
    {
        $days = min($request->get('days', 7), 30);
        
        $crypto = Cryptocurrency::where('coingecko_id', $coinGeckoId)->first();
        if (!$crypto) {
            return response()->json(['error' => 'Cryptocurrency not found'], 404);
        }

        $analyses = TrendAnalysis::where('cryptocurrency_id', $crypto->id)
            ->where('analysis_date', '>=', now()->subDays($days))
            ->orderBy('analysis_date', 'asc')
            ->get();

        $historyData = $analyses->map(function ($analysis) {
            return [
                'date' => $analysis->analysis_date->format('Y-m-d'),
                'sentiment_avg' => $analysis->sentiment_avg,
                'mention_count' => $analysis->mention_count,
                'trend_direction' => $analysis->trend_direction,
                'confidence_score' => $analysis->confidence_score,
                'sentiment_change' => $analysis->sentiment_change,
                'hourly_breakdown' => $analysis->hourly_breakdown,
            ];
        });

        // Calculate summary stats
        $totalMentions = $analyses->sum('mention_count');
        $avgSentiment = $analyses->avg('sentiment_avg');
        $sentimentTrend = $analyses->count() > 1 
            ? $analyses->last()->sentiment_avg - $analyses->first()->sentiment_avg 
            : 0;

        return response()->json([
            'cryptocurrency' => [
                'id' => $crypto->id,
                'name' => $crypto->name,
                'symbol' => $crypto->symbol,
                'image' => $crypto->image,
                'coingecko_id' => $crypto->coingecko_id,
            ],
            'history' => $historyData,
            'summary' => [
                'total_mentions' => $totalMentions,
                'avg_sentiment' => round($avgSentiment, 2),
                'sentiment_trend' => round($sentimentTrend, 2),
                'days_analyzed' => $analyses->count(),
            ],
            'period' => [
                'days' => $days,
                'from' => now()->subDays($days)->format('Y-m-d'),
                'to' => now()->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Search cryptocurrencies for adding to watchlist
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $cryptos = Cryptocurrency::where('name', 'like', "%{$query}%")
            ->orWhere('symbol', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name', 'symbol', 'image', 'current_price_pln', 'daily_mentions', 'current_sentiment']);

        // Add watchlist status
        $user = Auth::user();
        $watchlistIds = $user->watchlist()->pluck('cryptocurrency_id')->toArray();

        $results = $cryptos->map(function ($crypto) use ($watchlistIds) {
            return [
                'id' => $crypto->id,
                'name' => $crypto->name,
                'symbol' => $crypto->symbol,
                'image' => $crypto->image,
                'current_price_pln' => $crypto->current_price_pln,
                'daily_mentions' => $crypto->daily_mentions ?? 0,
                'current_sentiment' => $crypto->current_sentiment ?? 0,
                'is_watchlisted' => in_array($crypto->id, $watchlistIds),
            ];
        });

        return response()->json($results);
    }

    /**
     * Get discovery stats and overview
     */
    public function stats()
    {
        $today = today();
        
        // Get overall stats
        $totalCryptosAnalyzed = Cryptocurrency::where('sentiment_updated_at', '>=', $today)->count();
        $totalMentionsToday = Cryptocurrency::where('sentiment_updated_at', '>=', $today)->sum('daily_mentions');
        $avgSentimentToday = Cryptocurrency::where('sentiment_updated_at', '>=', $today)->avg('current_sentiment');

        // Get top performers
        $topPositive = Cryptocurrency::where('sentiment_updated_at', '>=', $today)
            ->where('current_sentiment', '>', 0)
            ->orderBy('current_sentiment', 'desc')
            ->limit(3)
            ->get(['name', 'symbol', 'image', 'current_sentiment', 'daily_mentions']);

        $topNegative = Cryptocurrency::where('sentiment_updated_at', '>=', $today)
            ->where('current_sentiment', '<', 0)
            ->orderBy('current_sentiment', 'asc')
            ->limit(3)
            ->get(['name', 'symbol', 'image', 'current_sentiment', 'daily_mentions']);

        $mostMentioned = Cryptocurrency::where('sentiment_updated_at', '>=', $today)
            ->orderBy('daily_mentions', 'desc')
            ->limit(5)
            ->get(['name', 'symbol', 'image', 'current_sentiment', 'daily_mentions']);

        return response()->json([
            'overview' => [
                'total_cryptos_analyzed' => $totalCryptosAnalyzed,
                'total_mentions_today' => $totalMentionsToday,
                'avg_sentiment_today' => round($avgSentimentToday ?? 0, 2),
                'last_updated' => now()->diffForHumans(),
            ],
            'top_performers' => [
                'most_positive' => $topPositive,
                'most_negative' => $topNegative,
                'most_mentioned' => $mostMentioned,
            ],
        ]);
    }
}