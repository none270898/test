<?php

namespace App\Notifications;

use App\Models\Cryptocurrency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SentimentAlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Cryptocurrency $crypto)
    {
    }

    public function via($notifiable): array
    {
        $channels = [];
        
        if ($notifiable->email_notifications) {
            $channels[] = 'mail';
        }
        
        if (config('services.onesignal.app_id')) {
            $channels[] = 'onesignal';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $sentiment = $this->crypto->current_sentiment ?? 0;
        $mentions = $this->crypto->daily_mentions ?? 0;
        $change = $this->crypto->sentiment_change_24h ?? 0;

        $alertType = $sentiment > 0.3 ? 'pozytywny' : ($sentiment < -0.3 ? 'negatywny' : 'znaczący');
        $emoji = $sentiment > 0.3 ? '🚀' : ($sentiment < -0.3 ? '📉' : '⚡');

        return (new MailMessage)
            ->subject("{$emoji} Smart Alert: {$this->crypto->name}")
            ->view('emails.sentiment-alert-triggered', [
                'user' => $notifiable,
                'crypto' => $this->crypto,
                'sentiment' => $sentiment,
                'mentions' => $mentions,
                'change' => $change,
                'alertType' => $alertType,
                'emoji' => $emoji,
            ]);
    }

    public function toOneSignal($notifiable): array
    {
        $sentiment = $this->crypto->current_sentiment ?? 0;
        $mentions = $this->crypto->daily_mentions ?? 0;
        
        $alertType = $sentiment > 0.3 ? 'pozytywny buzz' : ($sentiment < -0.3 ? 'negatywny sentiment' : 'wysoka aktywność');
        $emoji = $sentiment > 0.3 ? '🚀' : ($sentiment < -0.3 ? '📉' : '⚡');

        return [
            'contents' => [
                'pl' => "{$this->crypto->name}: {$alertType} ({$mentions} wzmianek, sentiment: " . number_format($sentiment, 2) . ")"
            ],
            'headings' => [
                'pl' => "{$emoji} Smart Alert: {$this->crypto->name}"
            ],
            'data' => [
                'type' => 'sentiment_alert',
                'cryptocurrency' => $this->crypto->symbol,
                'sentiment' => $sentiment,
                'mentions' => $mentions,
            ],
            'web_url' => route('dashboard'),
        ];
    }
}