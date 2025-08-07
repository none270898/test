<?php
// app/Notifications/PriceAlertTriggered.php

namespace App\Notifications;

use App\Models\PriceAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceAlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private PriceAlert $alert)
    {
    }

    public function via($notifiable): array
    {
        $channels = [];
        
        if ($notifiable->email_notifications) {
            $channels[] = 'mail';
        }
        
        // Add OneSignal if configured
        if (config('services.onesignal.app_id') && config('services.onesignal.rest_api_key')) {
            $channels[] = 'onesignal';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $crypto = $this->alert->cryptocurrency;
        $currentPrice = $this->alert->currency === 'PLN' 
            ? $crypto->current_price_pln 
            : $crypto->current_price_usd;

        $direction = $this->alert->type === 'above' ? 'wzrosła powyżej' : 'spadła poniżej';
        $currency = $this->alert->currency;
        
        return (new MailMessage)
            ->subject("🚨 Alert cenowy: {$crypto->name}")
            ->view('emails.price-alert-triggered', [
                'user' => $notifiable,
                'alert' => $this->alert,
                'crypto' => $crypto,
                'currentPrice' => $currentPrice,
                'direction' => $direction,
                'currency' => $currency,
            ]);
    }

    public function toOneSignal($notifiable): array
    {
        $crypto = $this->alert->cryptocurrency;
        $currentPrice = $this->alert->currency === 'PLN' 
            ? $crypto->current_price_pln 
            : $crypto->current_price_usd;

        $direction = $this->alert->type === 'above' ? 'wzrosła powyżej' : 'spadła poniżej';
        $currency = $this->alert->currency;
        
        return [
            'contents' => [
                'pl' => "Cena {$crypto->name} {$direction} {$this->alert->target_price} {$currency}. Aktualna cena: " . number_format($currentPrice, 2) . " {$currency}"
            ],
            'headings' => [
                'pl' => "🚨 Alert cenowy: {$crypto->name}"
            ],
            'data' => [
                'type' => 'price_alert',
                'cryptocurrency' => $crypto->symbol,
                'price' => $currentPrice,
            ],
            'web_url' => route('dashboard'),
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'price_alert',
            'cryptocurrency_id' => $this->alert->cryptocurrency_id,
            'alert_type' => $this->alert->type,
            'target_price' => $this->alert->target_price,
            'current_price' => $this->alert->currency === 'PLN' 
                ? $this->alert->cryptocurrency->current_price_pln 
                : $this->alert->cryptocurrency->current_price_usd,
            'currency' => $this->alert->currency,
        ];
    }
}