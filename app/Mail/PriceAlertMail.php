<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\PriceAlert;

class PriceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $alert;
    public $crypto;
    public $currentPrice;

    public function __construct(PriceAlert $alert)
    {
        $this->alert = $alert;
        $this->crypto = $alert->cryptocurrency;
        $this->currentPrice = $alert->currency === 'PLN' 
            ? $this->crypto->current_price_pln 
            : $this->crypto->current_price_usd;
    }

    public function build()
    {
        return $this->subject("Alert cenowy: {$this->crypto->symbol}")
                    ->view('emails.price-alert');
    }
}