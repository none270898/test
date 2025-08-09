<?php
namespace App\Console\Commands;

use App\Jobs\ScrapeSentimentData;
use App\Jobs\AnalyzeTrends;
use Illuminate\Console\Command;

class SentimentPipelineCommand extends Command
{
    protected $signature = 'sentiment:pipeline {--async : Run jobs asynchronously}';
    protected $description = 'Run complete sentiment analysis pipeline';

    public function handle(): int
    {
        $this->info('🤖 Starting AI Sentiment Analysis Pipeline');
        
        $async = $this->option('async');
        
        if ($async) {
            $this->info('Running in async mode (using queue)...');
            
            // Dispatch scraping job
            $this->line('1. Dispatching sentiment scraping...');
            ScrapeSentimentData::dispatch();
            
            // Dispatch analysis job with delay to allow scraping to complete
            $this->line('2. Dispatching trend analysis (delayed 5 minutes)...');
            AnalyzeTrends::dispatch()->delay(now()->addMinutes(5));
            
        } else {
            $this->info('Running in sync mode...');
            
            // Run scraping synchronously
            $this->line('1. Running sentiment scraping...');
            $this->call('sentiment:scrape');
            
            $this->line('2. Waiting 30 seconds before analysis...');
            sleep(30);
            
            // Run analysis synchronously
            $this->line('3. Running trend analysis...');
            $this->call('sentiment:analyze');
        }
        
        $this->info('✅ Sentiment analysis pipeline completed!');
        
        return self::SUCCESS;
    }
}