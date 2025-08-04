<?php
// app/Notifications/PriceAlertTriggered.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\OneSignalService;
use App\Models\PriceAlert;

class PriceAlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    private $alert;

    public function __construct(PriceAlert $alert)
    {
        $this->alert = $alert;
    }

    public function via($notifiable)
    {
        $channels = [];
        
        if ($this->alert->email_notification && $notifiable->email_notifications) {
            $channels[] = 'mail';
        }
        
        if ($this->alert->push_notification && $notifiable->push_notifications) {
            $channels[] = 'database';
        }
        
        return $channels;
    }

    public function toMail($notifiable)
    {
        $crypto = $this->alert->cryptocurrency;
        $currentPrice = $this->alert->currency === 'PLN' 
            ? $crypto->current_price_pln 
            : $crypto->current_price_usd;

        return (new MailMessage)
            ->subject("Alert cenowy: {$crypto->symbol}")
            ->greeting("Witaj {$notifiable->name}!")
            ->line("Twój alert cenowy został uruchomiony!")
            ->line("**{$crypto->name} ({$crypto->symbol})**")
            ->line("Aktualna cena: **{$currentPrice} {$this->alert->currency}**")
            ->line("Cena docelowa: {$this->alert->target_price} {$this->alert->currency}")
            ->line("Typ alertu: " . ($this->alert->alert_type === 'above' ? 'Powyżej' : 'Poniżej'))
            ->action('Zobacz portfolio', url('/portfolio'))
            ->line('Dziękujemy za używanie CryptoNote.pl!');
    }

    public function toArray($notifiable)
    {
        $crypto = $this->alert->cryptocurrency;
        $currentPrice = $this->alert->currency === 'PLN' 
            ? $crypto->current_price_pln 
            : $crypto->current_price_usd;

        return [
            'type' => 'price_alert',
            'title' => "Alert cenowy: {$crypto->symbol}",
            'message' => $crypto->symbol . ' ' .( $this->alert->alert_type === 'above' ? 'powyżej' : 'poniżej') . $currentPrice. ' ' . $this->alert->currency,
            'alert_id' => $this->alert->id,
            'cryptocurrency_id' => $crypto->id,
            'current_price' => $currentPrice,
            'target_price' => $this->alert->target_price,
            'currency' => $this->alert->currency,
        ];
    }
}
