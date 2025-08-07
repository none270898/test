<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\UpdateCryptoPrices::class,
        Commands\CheckPriceAlerts::class,
        Commands\UpdatePortfolioStats::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Update crypto prices every 5 minutes
        $schedule->command('crypto:update-prices')
                ->everyFiveMinutes()
                ->withoutOverlapping();

        // Check price alerts every minute
        $schedule->command('crypto:check-alerts')
                ->everyMinute()
                ->withoutOverlapping();

        // Update portfolio stats every 10 minutes
        $schedule->command('crypto:update-portfolios')
                ->everyTenMinutes()
                ->withoutOverlapping();

        // Scrape sentiment data every 30 minutes (Premium feature)
        $schedule->job(new \App\Jobs\ScrapeSentimentData)
                ->everyThirtyMinutes()
                ->withoutOverlapping();

        // Clean up old notifications (older than 30 days)
        $schedule->command('model:prune', ['--model' => 'App\Models\Notification'])
                ->daily()
                ->at('02:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
