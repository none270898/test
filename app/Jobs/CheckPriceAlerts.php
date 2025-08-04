<?php
namespace App\Jobs;

use App\Models\PriceAlert;
use App\Notifications\PriceAlertTriggered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckPriceAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            $alerts = PriceAlert::where('is_active', true)
                ->whereNull('triggered_at')
                ->with(['user', 'cryptocurrency'])
                ->get();

            foreach ($alerts as $alert) {
                if ($alert->shouldTrigger()) {
                    // Oznacz alert jako uruchomiony
                    $alert->update(['triggered_at' => now()]);
                    
                    // Wyślij powiadomienie
                    $alert->user->notify(new PriceAlertTriggered($alert));
                    
                    Log::info("Price alert triggered for user {$alert->user->id}: {$alert->cryptocurrency->symbol}");
                }
            }

        } catch (\Exception $e) {
            Log::error('Error checking price alerts: ' . $e->getMessage());
        }
    }
}