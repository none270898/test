<?php
namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\TrendAnalysis;
use App\Services\SentimentAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
     * Get sentiment history for a cryptocurrency - NAPRAWIONE
     */
    public function history(Request $request, string $coinGeckoId)
    {
        $days = min($request->get('days', 7), 30);
        
        Log::info('Discovery history request', [
            'coingecko_id' => $coinGeckoId,
            'days' => $days
        ]);
        
        // Find cryptocurrency by coingecko_id
        $crypto = Cryptocurrency::where('coingecko_id', $coinGeckoId)->first();
        if (!$crypto) {
            Log::warning('Cryptocurrency not found', ['coingecko_id' => $coinGeckoId]);
            return response()->json(['error' => 'Cryptocurrency not found'], 404);
        }

        // Get trend analyses for this crypto in the specified period - now with better hourly support
        $analyses = TrendAnalysis::where('cryptocurrency_id', $crypto->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'asc')
            ->get();

        Log::info('Found trend analyses', [
            'crypto' => $crypto->name,
            'analyses_count' => $analyses->count(),
            'period_days' => $days,
            'hourly_analyses' => true
        ]);

        // Group by day and aggregate hourly data
        $dailyAnalyses = $analyses->groupBy(function ($analysis) {
            return $analysis->created_at->format('Y-m-d');
        })->map(function ($dayAnalyses, $date) {
            // Get the most recent analysis of the day for trend direction
            $latestAnalysis = $dayAnalyses->last();
            
            return [
                'date' => $date,
                'sentiment_avg' => round($dayAnalyses->avg('sentiment_avg'), 2),
                'mention_count' => $dayAnalyses->sum('mention_count'),
                'trend_direction' => $latestAnalysis->trend_direction,
                'confidence_score' => round($dayAnalyses->avg('confidence_score')),
                'sentiment_change' => $latestAnalysis->sentiment_change ?? 0,
                'hourly_breakdown' => $latestAnalysis->hourly_breakdown ?? [],
                'analyses_count' => $dayAnalyses->count(), // How many analyses this day
                'first_analysis' => $dayAnalyses->first()->created_at->format('H:i'),
                'last_analysis' => $latestAnalysis->created_at->format('H:i'),
            ];
        })->values();

        Log::info('Found trend analyses', [
            'crypto' => $crypto->name,
            'analyses_count' => $analyses->count(),
            'period_days' => $days
        ]);

        // If no analyses found, try to get at least current data from cryptocurrency table
        if ($dailyAnalyses->isEmpty()) {
            // Create mock data point from current cryptocurrency data
            $mockHistoryData = [];
            
            if ($crypto->sentiment_updated_at) {
                $mockHistoryData[] = [
                    'date' => $crypto->sentiment_updated_at->format('Y-m-d'),
                    'sentiment_avg' => $crypto->current_sentiment ?? 0,
                    'mention_count' => $crypto->daily_mentions ?? 0,
                    'trend_direction' => 'neutral',
                    'confidence_score' => 50,
                    'sentiment_change' => $crypto->sentiment_change_24h ?? 0,
                    'hourly_breakdown' => [],
                ];
            }

            return response()->json([
                'cryptocurrency' => [
                    'id' => $crypto->id,
                    'name' => $crypto->name,
                    'symbol' => $crypto->symbol,
                    'image' => $crypto->image,
                    'coingecko_id' => $crypto->coingecko_id,
                ],
                'history' => $mockHistoryData,
                'summary' => [
                    'total_mentions' => $crypto->daily_mentions ?? 0,
                    'avg_sentiment' => round($crypto->current_sentiment ?? 0, 2),
                    'sentiment_trend' => round($crypto->sentiment_change_24h ?? 0, 2),
                    'days_analyzed' => count($mockHistoryData),
                ],
                'period' => [
                    'days' => $days,
                    'from' => now()->subDays($days)->format('Y-m-d'),
                    'to' => now()->format('Y-m-d'),
                ],
            ]);
        }

        // Transform daily analyses data for the response
        $historyData = $dailyAnalyses;

        // Calculate summary stats from daily aggregated data
        $totalMentions = $dailyAnalyses->sum('mention_count');
        $avgSentiment = $dailyAnalyses->avg('sentiment_avg');
        $sentimentTrend = $dailyAnalyses->count() > 1 
            ? $dailyAnalyses->last()['sentiment_avg'] - $dailyAnalyses->first()['sentiment_avg']
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
                'days_analyzed' => $dailyAnalyses->count(),
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
     * Get discovery stats and overview - IMPROVED to use cryptocurrency fields
     */
    public function stats()
    {
        // Get stats from cryptocurrency table (now properly updated)
        $recentlyUpdated = Cryptocurrency::where('sentiment_updated_at', '>=', now()->subDay())->get();
        
        $totalCryptosAnalyzed = $recentlyUpdated->count();
        $totalMentionsToday = $recentlyUpdated->sum('daily_mentions');
        $avgSentimentToday = $recentlyUpdated->avg('current_sentiment');

        // Get top performers using the updated fields
        $topPositive = Cryptocurrency::where('sentiment_updated_at', '>=', now()->subDay())
            ->where('current_sentiment', '>', 0)
            ->where('daily_mentions', '>', 0) // Must have mentions to be relevant
            ->orderBy('current_sentiment', 'desc')
            ->limit(3)
            ->get(['name', 'symbol', 'image', 'current_sentiment', 'daily_mentions']);

        $topNegative = Cryptocurrency::where('sentiment_updated_at', '>=', now()->subDay())
            ->where('current_sentiment', '<', 0)
            ->where('daily_mentions', '>', 0)
            ->orderBy('current_sentiment', 'asc')
            ->limit(3)
            ->get(['name', 'symbol', 'image', 'current_sentiment', 'daily_mentions']);

        $mostMentioned = Cryptocurrency::where('sentiment_updated_at', '>=', now()->subDay())
            ->where('daily_mentions', '>', 0)
            ->orderBy('daily_mentions', 'desc')
            ->limit(5)
            ->get(['name', 'symbol', 'image', 'current_sentiment', 'daily_mentions']);

        // Get trending distribution
        $trendingDistribution = [
            'hot' => Cryptocurrency::where('trending_score', '>', 80)->count(),
            'trending' => Cryptocurrency::whereBetween('trending_score', [50, 80])->count(),
            'moderate' => Cryptocurrency::whereBetween('trending_score', [20, 50])->count(),
            'low' => Cryptocurrency::where('trending_score', '<=', 20)->count(),
        ];

        return response()->json([
            'overview' => [
                'total_cryptos_analyzed' => $totalCryptosAnalyzed,
                'total_mentions_today' => $totalMentionsToday,
                'avg_sentiment_today' => round($avgSentimentToday ?? 0, 2),
                'last_updated' => now()->diffForHumans(),
                'trending_distribution' => $trendingDistribution,
            ],
            'top_performers' => [
                'most_positive' => $topPositive,
                'most_negative' => $topNegative,
                'most_mentioned' => $mostMentioned,
            ],
        ]);
    }
}