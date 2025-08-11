<?php
namespace App\Jobs;

use App\Models\Cryptocurrency;
use App\Services\SentimentAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeTrends implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $hoursBack = 6) // 6-hour window for smoother trends
    {
    }

    public function handle(SentimentAnalysisService $sentimentService): void
    {
        Log::info('Starting trend analysis...', [
            'hours_back' => $this->hoursBack
        ]);

        try {
            // Get cryptocurrencies to analyze - prioritize watchlisted ones + all with price data
            $cryptosToAnalyze = $this->getCryptocurrenciesToAnalyze();

            $analyzedCount = 0;

            foreach ($cryptosToAnalyze as $crypto) {
                try {
                    $analysis = $sentimentService->generateTrendAnalysis(
                        $crypto->coingecko_id,
                        $this->hoursBack
                    );

                    if ($analysis) {
                        $analyzedCount++;
                        Log::debug('Trend analysis created', [
                            'crypto' => $crypto->name,
                            'sentiment' => $analysis->sentiment_avg,
                            'mentions' => $analysis->mention_count,
                            'trend' => $analysis->trend_direction
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    Log::warning('Failed to analyze trends for crypto', [
                        'crypto' => $crypto->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Trend analysis completed', [
                'analyzed_cryptos' => $analyzedCount,
                'total_cryptos' => $cryptosToAnalyze->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Trend analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get cryptocurrencies to analyze - prioritize watchlisted ones and all with price data
     */
    private function getCryptocurrenciesToAnalyze()
    {
        // First get all cryptocurrencies that are in someone's watchlist
        $watchlistedCryptos = Cryptocurrency::whereHas('watchlistedBy')
            ->whereNotNull('current_price_pln')
            ->where('current_price_pln', '>', 0)
            ->get();

        // Get top cryptocurrencies by market cap/price that aren't already in watchlists
        $topCryptos = Cryptocurrency::whereNotNull('current_price_pln')
            ->where('current_price_pln', '>', 0)
            ->whereNotIn('id', $watchlistedCryptos->pluck('id'))
            ->orderBy('current_price_usd', 'desc')
            ->limit(50) // Increased from 20 to 50
            ->get();

        // Merge watchlisted cryptos with top cryptos
        $allCryptos = $watchlistedCryptos->merge($topCryptos);

        Log::info('Selected cryptocurrencies for analysis', [
            'watchlisted_count' => $watchlistedCryptos->count(),
            'top_cryptos_count' => $topCryptos->count(),
            'total_count' => $allCryptos->count()
        ]);

        return $allCryptos;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeTrends job failed', [
            'error' => $exception->getMessage(),
            'hours_back' => $this->hoursBack
        ]);
    }
}