<?php
namespace App\Services;

use App\Models\Cryptocurrency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RedditScraperService
{
    private string $baseUrl = 'https://www.reddit.com';
    private SentimentAnalysisService $sentimentService;

    // Expanded crypto keywords mapping
    private array $cryptoKeywordsMap = [
        'bitcoin' => ['bitcoin', 'btc', 'bitcoiny'],
        'ethereum' => ['ethereum', 'eth', 'ether', 'vitalik'],
        'cardano' => ['cardano', 'ada'],
        'solana' => ['solana', 'sol'],
        'polkadot' => ['polkadot', 'dot'],
        'chainlink' => ['chainlink', 'link'],
        'polygon' => ['polygon', 'matic'],
        'dogecoin' => ['dogecoin', 'doge', 'shibes'],
        'shiba-inu' => ['shiba', 'shib', 'shibainu'],
        'avalanche-2' => ['avalanche', 'avax'],
        'binancecoin' => ['binance', 'bnb'],
        'ripple' => ['ripple', 'xrp'],
        'litecoin' => ['litecoin', 'ltc'],
        'tron' => ['tron', 'trx'],
        'uniswap' => ['uniswap', 'uni'],
    ];

    // Polish crypto subreddits + general ones
    private array $cryptoSubreddits = [
        'BitcoinPL',
        'kryptowaluty', 
        'CryptoCurrency',
        'Bitcoin',
        'ethereum',
        'altcoin',
        'CryptoMarkets',
        'CryptoCurrencies',
        'investing',
        'SecurityToken',
        'Polska', // General Polish sub that might have crypto content
    ];

    public function __construct(SentimentAnalysisService $sentimentService)
    {
        $this->sentimentService = $sentimentService;
    }

    /**
     * Scrape Reddit posts - now dynamic based on database cryptos
     */
    public function scrapeCryptoPosts(array $subreddits = null, int $limit = 25): int
    {
        if (!$subreddits) {
            $subreddits = $this->cryptoSubreddits;
        }

        // Get cryptocurrencies to track from database
        $cryptosToTrack = $this->getCryptocurrenciesToTrack();
        
        Log::info('Starting Reddit scraping', [
            'subreddits' => $subreddits,
            'cryptos_to_track' => $cryptosToTrack->count(),
            'limit_per_subreddit' => $limit
        ]);

        $processedCount = 0;

        foreach ($subreddits as $subreddit) {
            try {
                $posts = $this->fetchSubredditPosts($subreddit, $limit);
                
                foreach ($posts as $post) {
                    if ($this->processCryptoPost($post, $subreddit, $cryptosToTrack)) {
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

        Log::info('Reddit scraping completed', [
            'processed_posts' => $processedCount
        ]);

        return $processedCount;
    }

    /**
     * Get cryptocurrencies to track - prioritize watchlisted ones
     */
    private function getCryptocurrenciesToTrack()
    {
        // Get watchlisted cryptos + top cryptos by price
        $watchlisted = Cryptocurrency::whereHas('watchlistedBy')
            ->whereNotNull('current_price_pln')
            ->get(['id', 'coingecko_id', 'name', 'symbol']);

        $topCryptos = Cryptocurrency::whereNotNull('current_price_pln')
            ->where('current_price_pln', '>', 0)
            ->whereNotIn('id', $watchlisted->pluck('id'))
            ->orderBy('current_price_usd', 'desc')
            ->limit(30)
            ->get(['id', 'coingecko_id', 'name', 'symbol']);

        $allCryptos = $watchlisted->merge($topCryptos);

        // Build keyword map for these cryptos
        foreach ($allCryptos as $crypto) {
            if (!isset($this->cryptoKeywordsMap[$crypto->coingecko_id])) {
                // Auto-generate keywords for cryptos not in predefined map
                $this->cryptoKeywordsMap[$crypto->coingecko_id] = [
                    strtolower($crypto->name),
                    strtolower($crypto->symbol),
                ];
            }
        }

        return $allCryptos;
    }

    /**
     * Fetch posts from subreddit using Reddit JSON API
     */
    private function fetchSubredditPosts(string $subreddit, int $limit): array
    {
        $cacheKey = "reddit_posts_{$subreddit}_{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($subreddit, $limit) { // 5 min cache
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'User-Agent' => 'CryptoNote.pl Bot 1.0 (by /u/cryptonote_pl)'
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
     * Process a single Reddit post for crypto sentiment - improved detection
     */
    private function processCryptoPost(array $postData, string $subreddit, $cryptosToTrack): bool
    {
        $post = $postData['data'] ?? null;
        if (!$post) {
            return false;
        }

        $title = $post['title'] ?? '';
        $content = $post['selftext'] ?? '';
        $fullContent = $title . ' ' . $content;

        // Skip if we already processed this post
        $postId = $post['id'] ?? null;
        if (!$postId || $this->isPostAlreadyProcessed($postId)) {
            return false;
        }

        // Check if post mentions any cryptocurrency we're tracking
        $mentionedCryptos = $this->detectCryptocurrencies($fullContent, $cryptosToTrack);
        if (empty($mentionedCryptos)) {
            return false;
        }

        // Analyze sentiment
        $sentimentScore = $this->sentimentService->analyzeSentiment($fullContent);

        // Store sentiment data for each mentioned crypto
        foreach ($mentionedCryptos as $cryptoId) {
            $this->sentimentService->storeSentimentData(
                source: 'reddit',
                content: $fullContent,
                sentimentScore: $sentimentScore,
                keywords: [$cryptoId],
                sourceUrl: "https://reddit.com" . ($post['permalink'] ?? ''),
                author: $post['author'] ?? null,
                publishedAt: $post['created_utc'] ? \DateTime::createFromFormat('U', $post['created_utc']) : null
            );
        }

        // Mark as processed
        $this->markPostAsProcessed($postId);

        return true;
    }

    /**
     * Detect which cryptocurrencies are mentioned in text
     */
    private function detectCryptocurrencies(string $text, $cryptosToTrack): array
    {
        $text = strtolower($text);
        $mentionedCryptos = [];

        foreach ($cryptosToTrack as $crypto) {
            $keywords = $this->cryptoKeywordsMap[$crypto->coingecko_id] ?? [];
            
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    $mentionedCryptos[] = $crypto->coingecko_id;
                    break; // Don't add the same crypto multiple times
                }
            }
        }

        return array_unique($mentionedCryptos);
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

    /**
     * Scrape specific cryptocurrency subreddit
     */
    public function scrapeSpecificCrypto(string $coinGeckoId, int $limit = 50): int
    {
        $crypto = Cryptocurrency::where('coingecko_id', $coinGeckoId)->first();
        if (!$crypto) {
            return 0;
        }

        // Try to find dedicated subreddit for this crypto
        $dedicatedSubreddits = $this->findDedicatedSubreddits($crypto);
        
        $processedCount = 0;
        foreach ($dedicatedSubreddits as $subreddit) {
            $posts = $this->fetchSubredditPosts($subreddit, $limit);
            
            foreach ($posts as $post) {
                if ($this->processCryptoPost($post, $subreddit, collect([$crypto]))) {
                    $processedCount++;
                }
            }
        }

        return $processedCount;
    }

    /**
     * Find dedicated subreddits for specific crypto
     */
    private function findDedicatedSubreddits(Cryptocurrency $crypto): array
    {
        $subreddits = [];
        
        // Common patterns for crypto subreddits
        $patterns = [
            $crypto->symbol, // BTC
            $crypto->name, // Bitcoin
            strtolower($crypto->symbol), // btc
            strtolower($crypto->name), // bitcoin
            ucfirst(strtolower($crypto->name)), // Bitcoin
        ];

        foreach ($patterns as $pattern) {
            $subreddits[] = $pattern;
        }

        return array_unique($subreddits);
    }
}