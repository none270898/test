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

    public function __construct(private int $hoursBack = 24)
    {
    }

    public function handle(SentimentAnalysisService $sentimentService): void
    {
        Log::info('Starting trend analysis...', [
            'hours_back' => $this->hoursBack
        ]);

        try {
            // Get top cryptocurrencies to analyze
            $topCryptos = Cryptocurrency::whereNotNull('current_price_pln')
                ->where('current_price_pln', '>', 0)
                ->orderBy('current_price_usd', 'desc')
                ->limit(20)
                ->get();

            $analyzedCount = 0;

            foreach ($topCryptos as $crypto) {
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
                'total_cryptos' => $topCryptos->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Trend analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeTrends job failed', [
            'error' => $exception->getMessage(),
            'hours_back' => $this->hoursBack
        ]);
    }
}
