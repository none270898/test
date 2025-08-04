<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ScrapeSentimentData;

class ScrapeSentiment extends Command
{
    protected $signature = 'crypto:scrape-sentiment';
    protected $description = 'Scrape sentiment data from various sources';

    public function handle()
    {
        $this->info('Scraping sentiment data...');
        
        ScrapeSentimentData::dispatch();
        
        $this->info('Sentiment scraping completed!');
        
        return 0;
    }
}