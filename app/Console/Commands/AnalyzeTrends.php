<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\AnalyzeTrends;

class AnalyzeTrend extends Command
{
    protected $signature = 'crypto:analyze-trends';
    protected $description = 'Analyze cryptocurrency trends based on sentiment data';

    public function handle()
    {
        $this->info('Analyzing cryptocurrency trends...');
        
        AnalyzeTrends::dispatch();
        
        $this->info('Trend analysis completed!');
        
        return 0;
    }
}