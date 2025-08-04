<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\UpdateCryptoPrices::class,
        Commands\CheckAlerts::class,
        Commands\ScrapeSentiment::class,
        Commands\AnalyzeTrend::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Update cryptocurrency prices every 5 minutes
        $schedule->command('crypto:update-prices')->everyFiveMinutes();
        
        // Check price alerts every minute
        $schedule->command('crypto:check-alerts')->everyMinute();
        
        // Scrape sentiment data every 30 minutes
        $schedule->command('crypto:scrape-sentiment')->everyThirtyMinutes();
        
        // Analyze trends every hour
        $schedule->command('crypto:analyze-trends')->hourly();
        
        // Send weekly reports on Sundays at 9 AM
        $schedule->call(function () {
            $users = \App\Models\User::where('email_notifications', true)
                ->whereHas('portfolios')
                ->get();
            
            foreach ($users as $user) {
                app(\App\Services\NotificationService::class)->sendWeeklyReport($user);
            }
        })->weeklyOn(0, '9:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}