<?php
namespace App\Console\Commands;

use App\Jobs\ScrapeSentimentData;
use Illuminate\Console\Command;

class ScrapeSentimentCommand extends Command
{
    protected $signature = 'sentiment:scrape {--subreddits=* : Subreddits to scrape} {--limit=150 : Posts limit per subreddit}';
    protected $description = 'Scrape sentiment data from Reddit and other sources';

    public function handle(): int
    {
        $subreddits = $this->option('subreddits') ?: ['BitcoinPL', 'CryptoCurrency','BTC','XRP','bitcoin','bitfinex','solana','kryptowaluty'];
        $limit = (int) $this->option('limit');

        $this->info('Dispatching sentiment scraping job...');
        $this->table(['Parameter', 'Value'], [
            ['Subreddits', implode(', ', $subreddits)],
            ['Posts per subreddit', $limit],
        ]);

        ScrapeSentimentData::dispatch($subreddits, $limit);

        $this->info('Sentiment scraping job dispatched successfully!');
        $this->info('Check storage/logs/laravel.log for progress details.');
        
        return self::SUCCESS;
    }
}
