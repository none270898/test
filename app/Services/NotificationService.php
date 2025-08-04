<?php
namespace App\Services;

use App\Models\User;
use App\Services\OneSignalService;
use App\Services\EmailService;

class NotificationService
{
    private $oneSignalService;
    private $emailService;

    public function __construct(OneSignalService $oneSignalService, EmailService $emailService)
    {
        $this->oneSignalService = $oneSignalService;
        $this->emailService = $emailService;
    }

    public function sendPriceAlert($alert)
    {
        $user = $alert->user;
        $crypto = $alert->cryptocurrency;
        $currentPrice = $alert->currency === 'PLN' 
            ? $crypto->current_price_pln 
            : $crypto->current_price_usd;

        $title = "Alert cenowy: {$crypto->symbol}";
        $message = sprintf(
            "%s %s %.2f %s (cel: %.2f %s)",
            $crypto->symbol,
            $alert->alert_type === 'above' ? 'powyżej' : 'poniżej',
            $currentPrice,
            $alert->currency,
            $alert->target_price,
            $alert->currency
        );

        // Wyślij push notification
        if ($alert->push_notification) {
            $this->oneSignalService->sendToUser($user->id, $title, $message, [
                'type' => 'price_alert',
                'alert_id' => $alert->id,
                'cryptocurrency_id' => $crypto->id
            ]);
        }

        // Wyślij email
        if ($alert->email_notification) {
            $this->emailService->sendPriceAlert($user, $alert);
        }
    }

    public function sendTrendAlert($trendAnalysis)
    {
        $crypto = $trendAnalysis->cryptocurrency;
        $title = "Analiza trendu: {$crypto->symbol}";
        
        $message = sprintf(
            "%s Trend %s (pewność: %d%%, wspomnienia: %d)",
            $trendAnalysis->getTrendEmoji(),
            $trendAnalysis->trend_direction === 'up' ? 'wzrostowy' : 
            ($trendAnalysis->trend_direction === 'down' ? 'spadkowy' : 'neutralny'),
            $trendAnalysis->confidence_score,
            $trendAnalysis->mention_count
        );

        // Wyślij do użytkowników premium którzy mają tę kryptowalutę w portfolio
        $premiumUsers = User::where('subscription_plan', 'premium')
            ->where('subscription_expires_at', '>', now())
            ->where('push_notifications', true)
            ->whereHas('portfolios', function($query) use ($crypto) {
                $query->where('cryptocurrency_id', $crypto->id);
            })
            ->get();

        foreach ($premiumUsers as $user) {
            $this->oneSignalService->sendToUser($user->id, $title, $message, [
                'type' => 'trend_analysis',
                'cryptocurrency_id' => $crypto->id,
                'trend_direction' => $trendAnalysis->trend_direction
            ]);

            $this->emailService->sendTrendAnalysis($user, $trendAnalysis);
        }
    }

    public function sendWeeklyReport($user)
    {
        if (!$user->email_notifications) {
            return;
        }

        $portfolios = $user->portfolios()->with('cryptocurrency')->get();
        
        if ($portfolios->isEmpty()) {
            return;
        }

        $totalValue = $portfolios->sum('current_value');
        $totalProfitLoss = $portfolios->sum('profit_loss');
        $profitLossPercentage = $totalValue > 0 ? ($totalProfitLoss / ($totalValue - $totalProfitLoss)) * 100 : 0;

        $portfolioData = [
            'total_value' => $totalValue,
            'profit_loss' => $totalProfitLoss,
            'profit_loss_percentage' => $profitLossPercentage,
            'portfolios' => $portfolios
        ];

        $this->emailService->sendWeeklyReport($user, $portfolioData);
    }
}