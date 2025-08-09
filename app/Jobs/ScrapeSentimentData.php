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
        private array $subreddits = ['BitcoinPL', 'CryptoCurrency'],
        private int $postsLimit = 25
    ) {
    }

    public function handle(RedditScraperService $redditService): void
    {
        Log::info('Starting sentiment data scraping...', [
            'subreddits' => $this->subreddits,
            'limit' => $this->postsLimit
        ]);

        try {
            $processedCount = $redditService->scrapeCryptoPosts($this->subreddits, $this->postsLimit);
            
            Log::info('Sentiment scraping completed', [
                'processed_posts' => $processedCount
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
