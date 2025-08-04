<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $portfolioData;

    public function __construct(User $user, $portfolioData)
    {
        $this->user = $user;
        $this->portfolioData = $portfolioData;
    }

    public function build()
    {
        return $this->subject('Tygodniowy raport portfolio - CryptoNote.pl')
                    ->view('emails.weekly-report');
    }
}
