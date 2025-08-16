<?php
// 1. Job: CheckSentimentAlerts.php

namespace App\Jobs;

use App\Models\UserWatchlist;
use App\Models\SentimentAlert;
use App\Notifications\SentimentAlertTriggered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckSentimentAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Checking sentiment alerts...');

        // Get all users (free + premium) with notifications enabled in watchlist
        $watchlistItems = UserWatchlist::with(['user', 'cryptocurrency'])
            ->where('notifications_enabled', true)
            ->get();

        $triggeredCount = 0;
        $skippedCount = 0;

        foreach ($watchlistItems as $item) {
            $crypto = $item->cryptocurrency;
            $user = $item->user;

            // Check if crypto has recent sentiment data
            if (!$crypto->sentiment_updated_at || 
                $crypto->sentiment_updated_at->isBefore(now()->subHours(6))) {
                continue;
            }

            // Check daily limit for free users
            if (!$this->canSendAlert($user)) {
                $skippedCount++;
                continue;
            }

            // Check for significant sentiment changes
            if ($this->shouldTriggerSentimentAlert($crypto)) {
                $this->createAndSendAlert($user, $crypto);
                $triggeredCount++;
            }
        }

        Log::info("Sentiment alerts check completed. Triggered: {$triggeredCount}, Skipped (limit): {$skippedCount}");
    }

    private function canSendAlert($user): bool
    {
        if (($premium_for_only_test_purpose = 1) ) {
            return true; // Unlimited for premium
        }
        
        // Free users: max 1 alert per day
        $todayAlerts = SentimentAlert::where('user_id', $user->id)
            ->where('last_triggered_at', '>=', now()->startOfDay())
            ->count();
            
        return $todayAlerts < 1;
    }

    private function shouldTriggerSentimentAlert($crypto): bool
    {
        // Don't spam - only once per day per crypto
        $existingAlert = SentimentAlert::where('cryptocurrency_id', $crypto->id)
            ->where('last_triggered_at', '>=', now()->subDay())
            ->first();

        if ($existingAlert) {
            return false;
        }

        // Trigger conditions:
        // 1. High sentiment spike (> 0.5)
        // 2. High negative sentiment (< -0.5) 
        // 3. High mention count with moderate sentiment
        
        $sentiment = $crypto->current_sentiment ?? 0;
        $mentions = $crypto->daily_mentions ?? 0;
        $sentimentChange = $crypto->sentiment_change_24h ?? 0;

        return (
            ($sentiment > 0.5 && $mentions >= 10) || // Strong positive
            ($sentiment < -0.5 && $mentions >= 10) || // Strong negative  
            ($mentions >= 50 && abs($sentiment) > 0.2) || // High activity
            (abs($sentimentChange) > 0.3 && $mentions >= 15) // Big change
        );
    }

    private function createAndSendAlert($user, $crypto): void
    {
        try {
            // Create or update sentiment alert record
            SentimentAlert::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'cryptocurrency_id' => $crypto->id,
                ],
                [
                    'trigger_type' => 'sentiment_spike',
                    'threshold_value' => $crypto->current_sentiment,
                    'mention_threshold' => $crypto->daily_mentions,
                    'is_active' => true,
                    'last_triggered_at' => now(),
                ]
            );

            // Send notification
            $user->notify(new SentimentAlertTriggered($crypto));

            Log::info("Sentiment alert triggered", [
                'user_id' => $user->id,
                'crypto' => $crypto->name,
                'sentiment' => $crypto->current_sentiment,
                'mentions' => $crypto->daily_mentions
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send sentiment alert", [
                'user_id' => $user->id,
                'crypto' => $crypto->name,
                'error' => $e->getMessage()
            ]);
        }
    }
}