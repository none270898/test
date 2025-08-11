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

    // Expanded crypto keywords mapping - dynamically built
    private array $cryptoKeywordsMap = [];

    // Base crypto subreddits + general ones
    private array $baseSubreddits = [
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
     * Scrape from multiple free sources - Reddit + RSS feeds
     */
    public function scrapeCryptoPosts(array $subreddits = null, int $limit = 25): int
    {
        // Get cryptocurrencies to track from database
        $cryptosToTrack = $this->getCryptocurrenciesToTrack();
        
        Log::info('Starting multi-source scraping', [
            'cryptos_to_track' => $cryptosToTrack->count(),
            'limit_per_source' => $limit
        ]);

        $processedCount = 0;

        // 1. Scrape Reddit (existing + dynamic subreddits)
        $processedCount += $this->scrapeReddit($subreddits, $limit, $cryptosToTrack);

        // 2. Scrape cryptocurrency news RSS feeds (free)
        $processedCount += $this->scrapeRSSFeeds($cryptosToTrack);

        // 3. Scrape Google Trends data (free API)
        $processedCount += $this->scrapeGoogleTrends($cryptosToTrack);

        Log::info('Multi-source scraping completed', [
            'total_processed' => $processedCount
        ]);

        return $processedCount;
    }

    /**
     * Enhanced Reddit scraping with dynamic subreddits
     */
    private function scrapeReddit(?array $subreddits, int $limit, $cryptosToTrack): int
    {
        // Build dynamic subreddit list
        $allSubreddits = $this->buildDynamicSubreddits($subreddits, $cryptosToTrack);
        
        $processedCount = 0;

        foreach ($allSubreddits as $subreddit) {
            try {
                $posts = $this->fetchSubredditPosts($subreddit, $limit);
                
                foreach ($posts as $post) {
                    if ($this->processCryptoPost($post, $subreddit, $cryptosToTrack, 'reddit')) {
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
     * Scrape cryptocurrency RSS feeds (free sources)
     */
    private function scrapeRSSFeeds($cryptosToTrack): int
    {
        $rssFeeds = [
            'https://cointelegraph.com/rss',
            'https://decrypt.co/feed',
            'https://bitcoinist.com/feed/',
            'https://www.coindesk.com/arc/outboundfeeds/rss/',
            'https://cryptonews.com/news/feed/',
            'https://ambcrypto.com/feed/',
        ];

        $processedCount = 0;

        foreach ($rssFeeds as $feedUrl) {
            try {
                $processedCount += $this->processRSSFeed($feedUrl, $cryptosToTrack);
                sleep(3); // Rate limiting
            } catch (\Exception $e) {
                Log::error("RSS feed scraping failed", [
                    'feed' => $feedUrl,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processedCount;
    }

    /**
     * Process RSS feed
     */
    private function processRSSFeed(string $feedUrl, $cryptosToTrack): int
    {
        try {
            $response = Http::timeout(30)->get($feedUrl);
            
            if (!$response->successful()) {
                return 0;
            }

            $xml = simplexml_load_string($response->body());
            if (!$xml) {
                return 0;
            }

            $processedCount = 0;
            $items = $xml->channel->item ?? $xml->item ?? [];

            foreach (array_slice($items, 0, 20) as $item) {
                $title = (string)$item->title;
                $description = (string)($item->description ?? '');
                $content = $title . ' ' . strip_tags($description);
                $link = (string)$item->link;
                $pubDate = (string)$item->pubDate;

                // Check if content mentions tracked cryptocurrencies
                $mentionedCryptos = $this->detectCryptocurrencies($content, $cryptosToTrack);
                
                if (!empty($mentionedCryptos)) {
                    $sentimentScore = $this->sentimentService->analyzeSentiment($content);

                    foreach ($mentionedCryptos as $cryptoId) {
                        $this->sentimentService->storeSentimentData(
                            source: 'news',
                            content: substr($content, 0, 1000),
                            sentimentScore: $sentimentScore,
                            keywords: [$cryptoId],
                            sourceUrl: $link,
                            author: 'RSS Feed',
                            publishedAt: $pubDate ? new \DateTime($pubDate) : null
                        );
                        $processedCount++;
                    }
                }
            }

            return $processedCount;

        } catch (\Exception $e) {
            Log::error("RSS processing error", [
                'feed' => $feedUrl,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Scrape Google Trends data (free)
     */
    private function scrapeGoogleTrends($cryptosToTrack): int
    {
        // Google Trends doesn't have a free API, but we can scrape search volume indicators
        // from related free sources or use search volume proxies
        
        $processedCount = 0;
        
        foreach ($cryptosToTrack->take(10) as $crypto) { // Limit to avoid rate limiting
            try {
                // Use a free alternative - search for trending topics related to crypto
                $searchTerms = [
                    $crypto->name . ' price',
                    $crypto->symbol . ' crypto',
                    $crypto->name . ' kurs'
                ];

                foreach ($searchTerms as $term) {
                    // Simulate trend data based on recent mentions
                    // In production, you could integrate with other free APIs
                    $trendScore = $this->calculateTrendScore($crypto, $term);
                    
                    if ($trendScore > 0) {
                        $this->sentimentService->storeSentimentData(
                            source: 'trends',
                            content: "Search interest for: {$term}",
                            sentimentScore: 0, // Neutral for trend data
                            keywords: [$crypto->coingecko_id],
                            sourceUrl: null,
                            author: 'Trend Analysis',
                            publishedAt: now()
                        );
                        $processedCount++;
                    }
                }

                sleep(1); // Rate limiting
                
            } catch (\Exception $e) {
                Log::error("Trends scraping error", [
                    'crypto' => $crypto->name,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processedCount;
    }

    /**
     * Calculate trend score based on available data
     */
    private function calculateTrendScore($crypto, $term): int
    {
        // Simple trend calculation based on recent mentions and price changes
        $recentMentions = $crypto->daily_mentions ?? 0;
        $priceChange = abs($crypto->price_change_24h ?? 0);
        
        return min(100, ($recentMentions * 2) + ($priceChange * 10));
    }

    /**
     * Build dynamic subreddit list based on cryptocurrencies
     */
    private function buildDynamicSubreddits(?array $customSubreddits, $cryptosToTrack): array
    {
        if ($customSubreddits) {
            return array_merge($this->baseSubreddits, $customSubreddits);
        }

        $dynamicSubreddits = $this->baseSubreddits;

        // Add crypto-specific subreddits
        foreach ($cryptosToTrack->take(20) as $crypto) { // Limit to avoid too many requests
            $cryptoSubreddits = $this->findDedicatedSubreddits($crypto);
            $dynamicSubreddits = array_merge($dynamicSubreddits, $cryptoSubreddits);
        }

        return array_unique($dynamicSubreddits);
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
            ->limit(50) // Increased limit
            ->get(['id', 'coingecko_id', 'name', 'symbol']);

        $allCryptos = $watchlisted->merge($topCryptos);

        // Build keyword map for these cryptos
        $this->buildKeywordMap($allCryptos);

        return $allCryptos;
    }

    /**
     * Build keyword mapping for cryptocurrencies
     */
    private function buildKeywordMap($cryptos)
    {
        foreach ($cryptos as $crypto) {
            $this->cryptoKeywordsMap[$crypto->coingecko_id] = [
                strtolower($crypto->name),
                strtolower($crypto->symbol),
                // Add common variations
                str_replace(' ', '', strtolower($crypto->name)),
                $crypto->symbol . 'usd',
                $crypto->symbol . 'pln',
            ];
        }

        // Add predefined mappings for major cryptos
        $this->addPredefinedMappings();
    }

    /**
     * Add predefined keyword mappings for major cryptocurrencies
     */
    private function addPredefinedMappings()
    {
        $predefined = [
            'bitcoin' => ['bitcoin', 'btc', 'bitcoiny', 'bitkoina'],
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

        foreach ($predefined as $coinId => $keywords) {
            if (isset($this->cryptoKeywordsMap[$coinId])) {
                $this->cryptoKeywordsMap[$coinId] = array_merge(
                    $this->cryptoKeywordsMap[$coinId], 
                    $keywords
                );
            } else {
                $this->cryptoKeywordsMap[$coinId] = $keywords;
            }
        }
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
     * Process a single post for crypto sentiment - improved detection
     */
    private function processCryptoPost(array $postData, string $source, $cryptosToTrack, string $sourceType): bool
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
                source: $sourceType,
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
        return Cache::has("processed_{$postId}");
    }

    /**
     * Mark post as processed
     */
    private function markPostAsProcessed(string $postId): void
    {
        Cache::put("processed_{$postId}", true, 86400); // 24 hours
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
                if ($this->processCryptoPost($post, $subreddit, collect([$crypto]), 'reddit')) {
                    $processedCount++;
                }
            }
        }

        return $processedCount;
    }
}