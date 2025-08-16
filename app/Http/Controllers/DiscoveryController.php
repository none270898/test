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
     * Get trending cryptocurrencies with premium-based access
     */
    public function trending(Request $request)
    {
        $limit = min($request->get('limit', 20), 50);
        $user = Auth::user();
        $trending = $this->sentimentService->getTrendingCryptos($limit);

        // Mark which ones are already in user's watchlist
        $watchlistIds = $user->watchlist()->pluck('cryptocurrency_id')->toArray();

        $trendingWithAccess = array_map(function ($item) use ($watchlistIds, $user) {
            $item['is_watchlisted'] = in_array($item['id'], $watchlistIds);
            
            // ZMIENIONE: Ograniczone dane sentiment dla darmowych użytkowników
            if (!($premium_for_only_test_purpose = 1) ) {
                // Podstawowe dane dostępne dla wszystkich
                $basicData = [
                    'id' => $item['id'],
                    'coingecko_id' => $item['coingecko_id'],
                    'name' => $item['name'],
                    'symbol' => $item['symbol'],
                    'image' => $item['image'],
                    'current_price_pln' => $item['current_price_pln'],
                    'price_change_24h' => $item['price_change_24h'],
                    'is_watchlisted' => $item['is_watchlisted'],
                    
                    // DODANE: Ograniczone sentiment info
                    'has_sentiment_data' => isset($item['daily_mentions']) && $item['daily_mentions'] > 0,
                    'trending_tier' => $this->getTrendingTier($item['trending_score'] ?? 0),
                    'premium_required' => true,
                    
                    // Null dla premium-only data
                    'daily_mentions' => null,
                    'current_sentiment' => null,
                    'sentiment_change_24h' => null,
                    'trending_score' => null,
                    'confidence_score' => null,
                    'trend_direction' => null,
                    'emoji' => '🔒',
                    'updated_at' => $item['updated_at'] ?? 'Live prices',
                ];
                
                return $basicData;
            }
            
            // Pełne dane dla Premium
            return $item;
        }, $trending);

        return response()->json([
            'trending' => $trendingWithAccess,
            'total_count' => count($trendingWithAccess),
            'updated_at' => now()->toISOString(),
            'access_level' => [
                'is_premium' => ($premium_for_only_test_purpose = 1) ,
                'sentiment_access' => ($premium_for_only_test_purpose = 1) ,
                'features_available' => ($premium_for_only_test_purpose = 1)  
                    ? ['full_sentiment', 'trending_scores', 'confidence_scores', 'trend_direction']
                    : ['basic_prices', 'trending_tiers'],
                'upgrade_benefits' => [
                    'AI sentiment analysis',
                    'Detailed trending scores',
                    'Confidence indicators',
                    'Trend predictions',
                    'Historical sentiment data'
                ]
            ]
        ]);
    }

    /**
     * Get sentiment history - Premium only
     */
    public function history(Request $request, string $coinGeckoId)
    {
        $user = Auth::user();
        
        // DODANE: Sprawdzenie Premium access
        if (!($premium_for_only_test_purpose = 1) ) {
            return response()->json([
                'error' => 'Premium feature required',
                'message' => 'Sentiment history jest dostępne tylko dla użytkowników Premium.',
                'upgrade_required' => true,
                'feature' => 'Sentiment History',
                'premium_benefits' => [
                    'Pełna historia sentiment',
                    'Godzinowe analizy',
                    'Wykresy trendów',
                    'Prognozy AI',
                    'Porównania z rynkiem'
                ]
            ], 403);
        }

        $days = min($request->get('days', 7), 30);
        
        Log::info('Discovery history request', [
            'coingecko_id' => $coinGeckoId,
            'days' => $days,
            'user_premium' => true
        ]);
        
        // Find cryptocurrency by coingecko_id
        $crypto = Cryptocurrency::where('coingecko_id', $coinGeckoId)->first();
        if (!$crypto) {
            Log::warning('Cryptocurrency not found', ['coingecko_id' => $coinGeckoId]);
            return response()->json(['error' => 'Cryptocurrency not found'], 404);
        }

        // Get trend analyses for this crypto in the specified period
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
            $latestAnalysis = $dayAnalyses->last();
            
            return [
                'date' => $date,
                'sentiment_avg' => round($dayAnalyses->avg('sentiment_avg'), 2),
                'mention_count' => $dayAnalyses->sum('mention_count'),
                'trend_direction' => $latestAnalysis->trend_direction,
                'confidence_score' => round($dayAnalyses->avg('confidence_score')),
                'sentiment_change' => $latestAnalysis->sentiment_change ?? 0,
                'hourly_breakdown' => $latestAnalysis->hourly_breakdown ?? [],
                'analyses_count' => $dayAnalyses->count(),
                'first_analysis' => $dayAnalyses->first()->created_at->format('H:i'),
                'last_analysis' => $latestAnalysis->created_at->format('H:i'),
            ];
        })->values();

        // If no analyses found, try to get current data
        if ($dailyAnalyses->isEmpty()) {
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

        $historyData = $dailyAnalyses;
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
     * Search cryptocurrencies with basic info for all, sentiment for Premium
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $user = Auth::user();
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $cryptos = Cryptocurrency::where('name', 'like', "%{$query}%")
            ->orWhere('symbol', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name', 'symbol', 'image', 'current_price_pln', 'daily_mentions', 'current_sentiment', 'trending_score']);

        // Add watchlist status
        $watchlistIds = $user->watchlist()->pluck('cryptocurrency_id')->toArray();

        $results = $cryptos->map(function ($crypto) use ($watchlistIds, $user) {
            $baseData = [
                'id' => $crypto->id,
                'name' => $crypto->name,
                'symbol' => $crypto->symbol,
                'image' => $crypto->image,
                'current_price_pln' => $crypto->current_price_pln,
                'is_watchlisted' => in_array($crypto->id, $watchlistIds),
            ];

            // ZMIENIONE: Sentiment data tylko dla Premium
            if (($premium_for_only_test_purpose = 1) ) {
                $baseData['daily_mentions'] = $crypto->daily_mentions ?? 0;
                $baseData['current_sentiment'] = $crypto->current_sentiment ?? 0;
                $baseData['trending_score'] = $crypto->trending_score ?? 0;
                $baseData['has_sentiment_data'] = ($crypto->daily_mentions ?? 0) > 0;
            } else {
                // DODANE: Ograniczone dane dla darmowych
                $baseData['daily_mentions'] = null;
                $baseData['current_sentiment'] = null;
                $baseData['trending_score'] = null;
                $baseData['has_sentiment_data'] = ($crypto->daily_mentions ?? 0) > 0;
                $baseData['premium_required'] = true;
            }

            return $baseData;
        });

        return response()->json($results);
    }

    /**
     * Get discovery stats - Premium only
     */
    public function stats()
    {
        $user = Auth::user();
        
        // DODANE: Sprawdzenie Premium access
        if (!($premium_for_only_test_purpose = 1) ) {
            return response()->json([
                'error' => 'Premium feature required',
                'message' => 'Discovery stats są dostępne tylko dla użytkowników Premium.',
                'upgrade_required' => true,
                'feature' => 'Discovery Statistics',
                'preview_data' => [
                    'total_cryptos_tracked' => Cryptocurrency::whereNotNull('current_price_pln')->count(),
                    'premium_features' => [
                        'Detailed sentiment statistics',
                        'Top performers analysis',
                        'Market sentiment overview',
                        'Trending distribution',
                        'Source activity breakdown'
                    ]
                ]
            ], 403);
        }

        // Get stats from cryptocurrency table
        $recentlyUpdated = Cryptocurrency::where('sentiment_updated_at', '>=', now()->subDay())->get();
        
        $totalCryptosAnalyzed = $recentlyUpdated->count();
        $totalMentionsToday = $recentlyUpdated->sum('daily_mentions');
        $avgSentimentToday = $recentlyUpdated->avg('current_sentiment');

        // Get top performers
        $topPositive = Cryptocurrency::where('sentiment_updated_at', '>=', now()->subDay())
            ->where('current_sentiment', '>', 0)
            ->where('daily_mentions', '>', 0)
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

    /**
     * DODANE: Helper method to determine trending tier for free users
     */
    private function getTrendingTier(int $score): string
    {
        if ($score >= 80) return 'hot';
        if ($score >= 50) return 'trending';
        if ($score >= 20) return 'moderate';
        return 'low';
    }
}