<?php
// app/Jobs/ScrapeSentimentData.php

namespace App\Jobs;

use App\Services\RedditScraperService;
use App\Services\SentimentAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeSentimentData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ?array $subreddits = null,
        private int $postsLimit = 50 // Increased from 25
    ) {
    }

    public function handle(RedditScraperService $redditService): void
    {
        Log::info('Starting sentiment data scraping...', [
            'subreddits' => $this->subreddits,
            'limit' => $this->postsLimit
        ]);

        try {
            // Use default subreddits if none provided (now includes Polish ones)
            $subreddits = $this->subreddits ?? [
                'BitcoinPL', 
                'kryptowaluty',
                'CryptoCurrency',
                'Bitcoin',
                'ethereum',
                'altcoin',
                'CryptoMarkets',
                'investing'
            ];
            
            $processedCount = $redditService->scrapeCryptoPosts($subreddits, $this->postsLimit);
            
            Log::info('Sentiment scraping completed', [
                'processed_posts' => $processedCount,
                'subreddits_count' => count($subreddits)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Sentiment scraping failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ScrapeSentimentData job failed', [
            'error' => $exception->getMessage(),
            'subreddits' => $this->subreddits
        ]);
    }
}