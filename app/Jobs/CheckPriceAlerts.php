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

    public function handle(): void
    {
        Log::info('Checking price alerts...');

        $activeAlerts = PriceAlert::with(['cryptocurrency', 'user'])
            ->where('is_active', true)
            ->whereNull('triggered_at')
            ->get();

        $triggeredCount = 0;

        foreach ($activeAlerts as $alert) {
            if ($alert->shouldTrigger()) {
                $this->triggerAlert($alert);
                $triggeredCount++;
            }
        }

        Log::info("Price alerts check completed. Triggered: {$triggeredCount}");
    }

    private function triggerAlert(PriceAlert $alert): void
    {
        try {
            // Mark as triggered
            $alert->update([
                'triggered_at' => now(),
                'is_active' => false, // Deactivate after triggering
            ]);

            // Send notification
            $alert->user->notify(new PriceAlertTriggered($alert));

            Log::info("Alert triggered for user {$alert->user->id}: {$alert->cryptocurrency->name}");
        } catch (\Exception $e) {
            Log::error("Failed to trigger alert {$alert->id}: " . $e->getMessage());
        }
    }
}
