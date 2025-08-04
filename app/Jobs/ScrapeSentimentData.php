<?php
namespace App\Jobs;

use App\Models\SentimentData;
use App\Services\SentimentAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScrapeSentimentData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $sentimentAnalyzer;
    private $cryptoKeywords = ['BTC', 'ETH', 'ADA', 'DOT', 'LINK', 'bitcoin', 'ethereum', 'krypto'];

    public function __construct()
    {
        $this->sentimentAnalyzer = new SentimentAnalyzer();
    }

    public function handle()
    {
        try {
            $this->scrapeReddit();
            $this->scrapeTwitter();
            $this->scrapeBitcoinPl();
            
            Log::info('Sentiment data scraping completed');
            
        } catch (\Exception $e) {
            Log::error('Error scraping sentiment data: ' . $e->getMessage());
        }
    }

    private function scrapeReddit()
    {
        try {
            $subreddits = ['BitcoinPL', 'Polska', 'inwestowanie'];
            
            foreach ($subreddits as $subreddit) {
                $response = Http::get("https://www.reddit.com/r/{$subreddit}/hot.json", [
                    'limit' => 25
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    foreach ($data['data']['children'] as $post) {
                        $postData = $post['data'];
                        $content = $postData['title'] . ' ' . $postData['selftext'];
                        
                        $detectedKeywords = $this->detectCryptoKeywords($content);
                        
                        if (!empty($detectedKeywords)) {
                            $sentimentScore = $this->sentimentAnalyzer->analyze($content);
                            
                            SentimentData::updateOrCreate(
                                [
                                    'source' => 'reddit',
                                    'url' => 'https://reddit.com' . $postData['permalink']
                                ],
                                [
                                    'content' => substr($content, 0, 1000),
                                    'sentiment_score' => $sentimentScore,
                                    'keywords' => $detectedKeywords,
                                    'published_at' => now()->subSeconds($postData['created_utc']),
                                ]
                            );
                        }
                    }
                }
                
                sleep(2); // Rate limiting
            }
            
        } catch (\Exception $e) {
            Log::error('Error scraping Reddit: ' . $e->getMessage());
        }
    }

    private function scrapeTwitter()
    {
        try {
            // Twitter API v2 - wymaga bearer token
            $bearerToken = config('services.twitter.bearer_token');
            
            if (!$bearerToken) {
                return;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $bearerToken
            ])->get('https://api.twitter.com/2/tweets/search/recent', [
                'query' => '(bitcoin OR ethereum OR krypto) lang:pl -is:retweet',
                'max_results' => 100,
                'tweet.fields' => 'created_at,public_metrics,lang'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                foreach ($data['data'] ?? [] as $tweet) {
                    $detectedKeywords = $this->detectCryptoKeywords($tweet['text']);
                    
                    if (!empty($detectedKeywords)) {
                        $sentimentScore = $this->sentimentAnalyzer->analyze($tweet['text']);
                        
                        SentimentData::updateOrCreate(
                            [
                                'source' => 'twitter',
                                'url' => 'https://twitter.com/i/web/status/' . $tweet['id']
                            ],
                            [
                                'content' => substr($tweet['text'], 0, 1000),
                                'sentiment_score' => $sentimentScore,
                                'keywords' => $detectedKeywords,
                                'published_at' => $tweet['created_at'],
                            ]
                        );
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error scraping Twitter: ' . $e->getMessage());
        }
    }

    private function scrapeBitcoinPl()
    {
        try {
            // Scraping Bitcoin.pl RSS/latest posts
            $response = Http::get('https://bitcoin.pl/feed/');
            
            if ($response->successful()) {
                $xml = simplexml_load_string($response->body());
                
                foreach ($xml->channel->item as $item) {
                    $content = (string)$item->title . ' ' . strip_tags((string)$item->description);
                    $detectedKeywords = $this->detectCryptoKeywords($content);
                    
                    if (!empty($detectedKeywords)) {
                        $sentimentScore = $this->sentimentAnalyzer->analyze($content);
                        
                        SentimentData::updateOrCreate(
                            [
                                'source' => 'bitcoin_pl',
                                'url' => (string)$item->link
                            ],
                            [
                                'content' => substr($content, 0, 1000),
                                'sentiment_score' => $sentimentScore,
                                'keywords' => $detectedKeywords,
                                'published_at' => $item->pubDate,
                            ]
                        );
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error scraping Bitcoin.pl: ' . $e->getMessage());
        }
    }

    private function detectCryptoKeywords($text)
    {
        $text = strtolower($text);
        $found = [];
        
        foreach ($this->cryptoKeywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                $found[] = strtoupper($keyword);
            }
        }
        
        return array_unique($found);
    }
}
