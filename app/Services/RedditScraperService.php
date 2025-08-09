<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RedditScraperService
{
    private string $baseUrl = 'https://www.reddit.com';
    private SentimentAnalysisService $sentimentService;

    public function __construct(SentimentAnalysisService $sentimentService)
    {
        $this->sentimentService = $sentimentService;
    }

    /**
     * Scrape Reddit posts from crypto-related subreddits
     */
    public function scrapeCryptoPosts(array $subreddits = ['BitcoinPL'], int $limit = 25): int
    {
        $processedCount = 0;

        foreach ($subreddits as $subreddit) {
            try {
                $posts = $this->fetchSubredditPosts($subreddit, $limit);
                
                foreach ($posts as $post) {
                    if ($this->processCryptoPost($post, $subreddit)) {
                        $processedCount++;
                    }
                }

                // Rate limiting
                sleep(2);
                
            } catch (\Exception $e) {
                Log::error("Reddit scraping failed for r/{$subreddit}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processedCount;
    }

    /**
     * Fetch posts from subreddit using Reddit JSON API
     */
    private function fetchSubredditPosts(string $subreddit, int $limit): array
    {
        $cacheKey = "reddit_posts_{$subreddit}_{$limit}";
        
        return Cache::remember($cacheKey, 600, function () use ($subreddit, $limit) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'User-Agent' => 'CryptoNote.pl Bot 1.0'
                    ])
                    ->get("{$this->baseUrl}/r/{$subreddit}/hot.json", [
                        'limit' => $limit
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data']['children'] ?? [];
                }

                Log::warning("Reddit API error for r/{$subreddit}", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [];
                
            } catch (\Exception $e) {
                Log::error("Reddit fetch exception for r/{$subreddit}", [
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Process a single Reddit post for crypto sentiment
     */
    private function processCryptoPost(array $postData, string $subreddit): bool
    {
        $post = $postData['data'] ?? null;
        if (!$post) {
            return false;
        }

        $title = $post['title'] ?? '';
        $content = $post['selftext'] ?? '';
        $fullContent = $title . ' ' . $content;

        // Check if post mentions cryptocurrency
        $cryptoKeywords = $this->sentimentService->extractCryptoKeywords($fullContent);
        if (empty($cryptoKeywords)) {
            return false;
        }

        // Skip if we already processed this post
        $postId = $post['id'] ?? null;
        if (!$postId || $this->isPostAlreadyProcessed($postId)) {
            return false;
        }

        // Analyze sentiment
        $sentimentScore = $this->sentimentService->analyzeSentiment($fullContent);

        // Store sentiment data
        $this->sentimentService->storeSentimentData(
            source: 'reddit',
            content: $fullContent,
            sentimentScore: $sentimentScore,
            keywords: $cryptoKeywords,
            sourceUrl: "https://reddit.com" . ($post['permalink'] ?? ''),
            author: $post['author'] ?? null,
            publishedAt: $post['created_utc'] ? \DateTime::createFromFormat('U', $post['created_utc']) : null
        );

        // Mark as processed
        $this->markPostAsProcessed($postId);

        return true;
    }

    /**
     * Check if post was already processed
     */
    private function isPostAlreadyProcessed(string $postId): bool
    {
        return Cache::has("reddit_processed_{$postId}");
    }

    /**
     * Mark post as processed
     */
    private function markPostAsProcessed(string $postId): void
    {
        Cache::put("reddit_processed_{$postId}", true, 86400); // 24 hours
    }
}