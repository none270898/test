<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\TrendAnalysis;

class TrendAnalysisMail extends Mailable
{
    use Queueable, SerializesModels;

    public $analysis;
    public $crypto;

    public function __construct(TrendAnalysis $analysis)
    {
        $this->analysis = $analysis;
        $this->crypto = $analysis->cryptocurrency;
    }

    public function build()
    {
        return $this->subject("Analiza trendu: {$this->crypto->symbol}")
                    ->view('emails.trend-analysis');
    }
}