<?php
// app/Services/SentimentAnalysisService.php

namespace App\Services;

use App\Models\SentimentData;
use App\Models\TrendAnalysis;
use App\Models\Cryptocurrency;
use App\Models\User;
use App\Models\UserWatchlist;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SentimentAnalysisService
{
    private array $positiveWords = [
        'buy', 'hodl', 'moon', 'pump', 'bullish', 'green', 'up', 'rise', 'gain', 'profit',
        'kupuj', 'trzymaj', 'wzrost', 'zysk', 'plus', 'góra', 'rośnie', 'dobry', 'świetny'
    ];

    private array $negativeWords = [
        'sell', 'dump', 'crash', 'bearish', 'red', 'down', 'fall', 'loss', 'drop', 'dip',
        'sprzedaj', 'spadek', 'strata', 'minus', 'dół', 'spada', 'zły', 'kiepski', 'dramat'
    ];

    private array $cryptoKeywords = [
        'bitcoin' => ['bitcoin', 'btc'],
        'ethereum' => ['ethereum', 'eth', 'ether'],
        'cardano' => ['cardano', 'ada'],
        'solana' => ['solana', 'sol'],
        'polkadot' => ['polkadot', 'dot'],
        'chainlink' => ['chainlink', 'link'],
        'polygon' => ['polygon', 'matic'],
        'dogecoin' => ['dogecoin', 'doge'],
        'shiba-inu' => ['shiba', 'shib'],
        'avalanche-2' => ['avalanche', 'avax'],
    ];

    /**
     * Analyze sentiment of text content
     */
    public function analyzeSentiment(string $text): float
    {
        $text = strtolower($text);
        $words = preg_split('/\s+/', $text);
        
        $positiveCount = 0;
        $negativeCount = 0;
        $totalWords = count($words);

        foreach ($words as $word) {
            $word = preg_replace('/[^a-ząćęłńóśźż]/u', '', $word);
            
            if (in_array($word, $this->positiveWords)) {
                $positiveCount++;
            } elseif (in_array($word, $this->negativeWords)) {
                $negativeCount++;
            }
        }

        if ($positiveCount === 0 && $negativeCount === 0) {
            return 0.0; // Neutral
        }

        // Normalize sentiment score between -1 and 1
        $sentimentSum = $positiveCount - $negativeCount;
        $maxSentiment = max($positiveCount + $negativeCount, 1);
        
        return round($sentimentSum / $maxSentiment, 2);
    }

    /**
     * Extract cryptocurrency keywords from text
     */
    public function extractCryptoKeywords(string $text): array
    {
        $text = strtolower($text);
        $foundCryptos = [];

        foreach ($this->cryptoKeywords as $coinId => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    $foundCryptos[] = $coinId;
                    break; // Don't add the same crypto multiple times
                }
            }
        }

        return array_unique($foundCryptos);
    }

    /**
     * Store sentiment data
     */
    public function storeSentimentData(
        string $source,
        string $content,
        float $sentimentScore,
        array $keywords,
        ?string $sourceUrl = null,
        ?string $author = null,
        ?\DateTime $publishedAt = null
    ): SentimentData {
        return SentimentData::create([
            'source' => $source,
            'content' => substr($content, 0, 1000), // Limit content length
            'sentiment_score' => $sentimentScore,
            'keywords' => $keywords,
            'source_url' => $sourceUrl,
            'author' => $author,
            'published_at' => $publishedAt ?? now(),
        ]);
    }

    /**
     * Generate trend analysis for cryptocurrency - ZMIENIONE NA INSERT ZAMIAST UPDATE
     */
    public function generateTrendAnalysis(string $coinGeckoId, int $hoursBack = 24): ?TrendAnalysis
    {
        $crypto = Cryptocurrency::where('coingecko_id', $coinGeckoId)->first();
        if (!$crypto) {
            return null;
        }

        $startTime = now()->subHours($hoursBack);
        $endTime = now();

        // Get sentiment data for this cryptocurrency
        $sentimentData = SentimentData::where('published_at', '>=', $startTime)
            ->where('published_at', '<=', $endTime)
            ->whereJsonContains('keywords', $coinGeckoId)
            ->get();

        if ($sentimentData->isEmpty()) {
            Log::debug('No sentiment data found for crypto', [
                'coingecko_id' => $coinGeckoId,
                'hours_back' => $hoursBack
            ]);
            return null;
        }

        // Calculate metrics
        $sentimentAvg = $sentimentData->avg('sentiment_score');
        $mentionCount = $sentimentData->count();
        
        // Get hourly breakdown
        $hourlyBreakdown = $sentimentData->groupBy(function ($item) {
            return $item->published_at->format('H');
        })->map(function ($hourData) {
            return [
                'hour' => $hourData->first()->published_at->format('H:00'),
                'sentiment' => round($hourData->avg('sentiment_score'), 2),
                'mentions' => $hourData->count(),
            ];
        })->values()->toArray();
        
        // Determine trend direction
        $trendDirection = 'neutral';
        if ($sentimentAvg > 0.2 && $mentionCount >= 10) {
            $trendDirection = 'up';
        } elseif ($sentimentAvg < -0.2 && $mentionCount >= 10) {
            $trendDirection = 'down';
        }

        // Calculate confidence score (0-100)
        $confidenceScore = min(100, ($mentionCount * 2) + (abs($sentimentAvg) * 50));

        // Get previous sentiment for comparison (from previous analysis, not just previous day)
        $previousAnalysis = TrendAnalysis::where('cryptocurrency_id', $crypto->id)
            ->where('created_at', '<', $endTime) // Before current analysis period
            ->orderBy('created_at', 'desc')
            ->first();

        $previousSentiment = $previousAnalysis ? $previousAnalysis->sentiment_avg : 0;
        $sentimentChange = $sentimentAvg - $previousSentiment;

        $analysisData = [
            'cryptocurrency_id' => $crypto->id,
            'sentiment_avg' => round($sentimentAvg, 2),
            'mention_count' => $mentionCount,
            'trend_direction' => $trendDirection,
            'confidence_score' => round($confidenceScore),
            'analysis_period_start' => $startTime,
            'analysis_period_end' => $endTime,
            'analysis_date' => today(),
            'hourly_breakdown' => $hourlyBreakdown,
            'previous_sentiment' => round($previousSentiment, 2),
            'sentiment_change' => round($sentimentChange, 2),
        ];

        // Check if we have recent analysis (within last 30 minutes) to avoid spam
        $recentAnalysis = TrendAnalysis::where('cryptocurrency_id', $crypto->id)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->first();

        if ($recentAnalysis) {
            // Update recent analysis instead of creating duplicate
            $recentAnalysis->update($analysisData);
            $analysis = $recentAnalysis;
            Log::debug('Updated recent analysis', ['crypto' => $crypto->name, 'analysis_id' => $analysis->id]);
        } else {
            // Create new analysis entry for full history
            $analysis = TrendAnalysis::create($analysisData);
            Log::debug('Created new analysis', ['crypto' => $crypto->name, 'analysis_id' => $analysis->id]);
        }

        // AKTUALIZUJ POLA W CRYPTOCURRENCIES - critical step
        try {
            $this->updateCryptocurrencyMetrics($crypto, $sentimentAvg, $mentionCount, $sentimentChange);
        } catch (\Exception $e) {
            Log::error('Failed to update cryptocurrency metrics', [
                'crypto' => $crypto->name,
                'error' => $e->getMessage(),
                'sentiment' => $sentimentAvg,
                'mentions' => $mentionCount
            ]);
            // Don't throw - analysis was successful, just metrics update failed
        }

        Log::info('Trend analysis created', [
            'crypto' => $crypto->name,
            'analysis_id' => $analysis->id,
            'sentiment' => $sentimentAvg,
            'mentions' => $mentionCount
        ]);

        return $analysis;
    }

    /**
     * Update cryptocurrency with latest sentiment metrics - IMPROVED
     */
    private function updateCryptocurrencyMetrics(
        Cryptocurrency $crypto, 
        float $sentiment, 
        int $mentions, 
        float $sentimentChange
    ): void {
        // Calculate trending score with better weighting
        $mentionScore = min($mentions * 3, 150); // Cap mentions contribution at 150
        $sentimentScore = abs($sentiment) * 25; // Absolute sentiment strength
        $changeScore = abs($sentimentChange) * 20; // Sentiment change impact
        $priceScore = $crypto->current_price_usd > 1000 ? 10 : 0; // Bonus for high-value coins
        
        $trendingScore = $mentionScore + $sentimentScore + $changeScore + $priceScore;
        
        // Ensure minimum values
        $mentions = max($mentions, 0);
        $sentiment = max(-1, min(1, $sentiment)); // Clamp between -1 and 1
        
        $updateData = [
            'daily_mentions' => $mentions,
            'current_sentiment' => round($sentiment, 2),
            'sentiment_change_24h' => round($sentimentChange, 2),
            'trending_score' => round($trendingScore),
            'sentiment_updated_at' => now(),
        ];

        $updated = $crypto->update($updateData);

        Log::info('Updated cryptocurrency metrics', [
            'crypto' => $crypto->name,
            'updated' => $updated,
            'data' => $updateData,
            'trending_calculation' => [
                'mention_score' => $mentionScore,
                'sentiment_score' => $sentimentScore,
                'change_score' => $changeScore,
                'price_score' => $priceScore,
                'total' => $trendingScore
            ]
        ]);

        // Verify the update actually worked
        $crypto->refresh();
        if ($crypto->daily_mentions !== $mentions) {
            Log::error('Cryptocurrency update failed', [
                'crypto' => $crypto->name,
                'expected_mentions' => $mentions,
                'actual_mentions' => $crypto->daily_mentions
            ]);
        }
    }

    /**
     * Get trending cryptocurrencies for discovery - NAPRAWIONE
     */
    public function getTrendingCryptos(int $limit = 20): array
    {
        // Get cryptocurrencies with recent sentiment data (last 6 hours for more fresh data)
        $trending = Cryptocurrency::where('daily_mentions', '>', 0)
            ->where('sentiment_updated_at', '>=', now()->subHours(6)) // Changed from 2 days to 6 hours
            ->whereNotNull('current_price_pln')
            ->where('current_price_pln', '>', 0)
            ->orderBy('trending_score', 'desc')
            ->orderBy('daily_mentions', 'desc')
            ->limit($limit)
            ->get();

        Log::info('getTrendingCryptos query result', [
            'count' => $trending->count(),
            'limit' => $limit
        ]);

        // If no trending cryptos with sentiment, fallback to top cryptos by price
        if ($trending->isEmpty()) {
            Log::warning('No trending cryptos found, using fallback');
            
            $trending = Cryptocurrency::whereNotNull('current_price_pln')
                ->where('current_price_pln', '>', 0)
                ->orderBy('current_price_usd', 'desc')
                ->limit($limit)
                ->get();
        }

        return $trending->map(function ($crypto) {
            $latestAnalysis = $crypto->getLatestTrendAnalysis();
            
            return [
                'id' => $crypto->id,
                'coingecko_id' => $crypto->coingecko_id,
                'name' => $crypto->name,
                'symbol' => $crypto->symbol,
                'image' => $crypto->image,
                'current_price_pln' => $crypto->current_price_pln ?? 0,
                'price_change_24h' => $crypto->price_change_24h ?? 0,
                'daily_mentions' => $crypto->daily_mentions ?? 0,
                'current_sentiment' => $crypto->current_sentiment ?? 0,
                'sentiment_change_24h' => $crypto->sentiment_change_24h ?? 0,
                'trending_score' => $crypto->trending_score ?? 0,
                'trend_direction' => $latestAnalysis?->trend_direction ?? 'neutral',
                'confidence_score' => $latestAnalysis?->confidence_score ?? 0,
                'emoji' => $latestAnalysis?->getTrendEmoji() ?? '➡️',
                'updated_at' => $crypto->sentiment_updated_at ? $crypto->sentiment_updated_at->diffForHumans() : 'Never',
                'last_analysis_at' => $latestAnalysis?->created_at?->diffForHumans() ?? 'Never', // ADDED
            ];
        })->toArray();
    }

    /**
     * Get user's watchlist sentiment summary
     */
    public function getWatchlistSummary(User $user): array
    {
        $watchlistCryptos = $user->getWatchlistCryptos();
        
        if ($watchlistCryptos->isEmpty()) {
            return [];
        }

        $cryptoIds = $watchlistCryptos->pluck('id')->toArray();
        
        $analyses = TrendAnalysis::whereIn('cryptocurrency_id', $cryptoIds)
            ->where('created_at', '>=', now()->subHours(12)) // Last 12 hours instead of 1 day
            ->with('cryptocurrency')
            ->orderBy('created_at', 'desc') // Most recent first
            ->get()
            ->groupBy('cryptocurrency_id') // Group by crypto
            ->map(function ($cryptoAnalyses) {
                return $cryptoAnalyses->first(); // Take most recent analysis for each crypto
            });

        return $analyses->values()->map(function ($analysis) { // ->values() to flatten after groupBy
            return [
                'cryptocurrency' => [
                    'id' => $analysis->cryptocurrency->id,
                    'name' => $analysis->cryptocurrency->name,
                    'symbol' => $analysis->cryptocurrency->symbol,
                    'image' => $analysis->cryptocurrency->image,
                    'current_price_pln' => $analysis->cryptocurrency->current_price_pln,
                    'price_change_24h' => $analysis->cryptocurrency->price_change_24h,
                ],
                'sentiment_avg' => $analysis->sentiment_avg,
                'mention_count' => $analysis->mention_count,
                'trend_direction' => $analysis->trend_direction,
                'confidence_score' => $analysis->confidence_score,
                'sentiment_change' => $analysis->sentiment_change,
                'previous_sentiment' => $analysis->previous_sentiment,
                'emoji' => $analysis->getTrendEmoji(),
                'analysis_time' => $analysis->created_at->diffForHumans(),
                'hourly_breakdown' => $analysis->hourly_breakdown,
            ];
        })->toArray();
    }

    /**
     * Initialize default watchlist for new user
     */
    public function initializeDefaultWatchlist(User $user): void
    {
        // Get top 10 cryptocurrencies by market cap
        $topCryptos = Cryptocurrency::whereNotNull('current_price_usd')
            ->where('current_price_usd', '>', 0)
            ->orderBy('current_price_usd', 'desc')
            ->limit(10)
            ->get();

        foreach ($topCryptos as $crypto) {
            UserWatchlist::firstOrCreate([
                'user_id' => $user->id,
                'cryptocurrency_id' => $crypto->id,
            ], [
                'notifications_enabled' => true,
            ]);
        }
    }
}