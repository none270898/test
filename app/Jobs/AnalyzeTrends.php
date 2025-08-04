<?php
namespace App\Jobs;

use App\Models\Cryptocurrency;
use App\Models\SentimentData;
use App\Models\TrendAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeTrends implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            $cryptocurrencies = Cryptocurrency::whereIn('symbol', ['BTC', 'ETH', 'ADA', 'DOT', 'LINK'])->get();
            
            foreach ($cryptocurrencies as $crypto) {
                $this->analyzeCryptocurrency($crypto);
            }
            
            Log::info('Trend analysis completed');
            
        } catch (\Exception $e) {
            Log::error('Error analyzing trends: ' . $e->getMessage());
        }
    }

    private function analyzeCryptocurrency($cryptocurrency)
    {
        $periodStart = now()->subDay();
        $periodEnd = now();
        
        // Pobierz dane sentiment z ostatnich 24h
        $sentimentData = SentimentData::whereJsonContains('keywords', $cryptocurrency->symbol)
            ->whereBetween('published_at', [$periodStart, $periodEnd])
            ->get();

        if ($sentimentData->count() < 5) {
            return; // Za mało danych do analizy
        }

        $avgSentiment = $sentimentData->avg('sentiment_score');
        $mentionCount = $sentimentData->count();
        
        // Oblicz breakdown źródeł
        $sourceBreakdown = $sentimentData->groupBy('source')
            ->map(function($group) {
                return $group->avg('sentiment_score');
            })->toArray();

        // Określ kierunek trendu
        $trendDirection = 'neutral';
        $confidenceScore = 0;
        
        if ($avgSentiment > 0.3 && $mentionCount >= 10) {
            $trendDirection = 'up';
            $confidenceScore = min(100, ($avgSentiment * 100) + ($mentionCount * 2));
        } elseif ($avgSentiment < -0.3 && $mentionCount >= 10) {
            $trendDirection = 'down';
            $confidenceScore = min(100, (abs($avgSentiment) * 100) + ($mentionCount * 2));
        } else {
            $confidenceScore = max(0, ($mentionCount * 3) - 20);
        }

        // Zapisz analizę
        TrendAnalysis::create([
            'cryptocurrency_id' => $cryptocurrency->id,
            'sentiment_avg' => $avgSentiment,
            'mention_count' => $mentionCount,
            'trend_direction' => $trendDirection,
            'confidence_score' => $confidenceScore,
            'source_breakdown' => $sourceBreakdown,
            'analysis_period_start' => $periodStart,
            'analysis_period_end' => $periodEnd,
        ]);
    }
}