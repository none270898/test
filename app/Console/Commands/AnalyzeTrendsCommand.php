<?php
namespace App\Console\Commands;

use App\Jobs\AnalyzeTrends;
use Illuminate\Console\Command;

class AnalyzeTrendsCommand extends Command
{
    protected $signature = 'sentiment:analyze {--hours=24 : Hours back to analyze}';
    protected $description = 'Analyze cryptocurrency trends based on sentiment data';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $this->info('Dispatching trend analysis job...');
        $this->line("Analyzing sentiment data from the last {$hours} hours");

        AnalyzeTrends::dispatch($hours);

        $this->info('Trend analysis job dispatched successfully!');
        $this->info('Check storage/logs/laravel.log for analysis details.');
        
        return self::SUCCESS;
    }
}