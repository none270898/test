<?php
namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\PriceAlertMail;
use App\Mail\TrendAnalysisMail;
use App\Mail\WeeklyReportMail;

class EmailService
{
    public function sendPriceAlert($user, $alert)
    {
        if (!$user->email_notifications || !$alert->email_notification) {
            return;
        }

        try {
            Mail::to($user->email)->send(new PriceAlertMail($alert));
        } catch (\Exception $e) {
            \Log::error('Failed to send price alert email: ' . $e->getMessage());
        }
    }

    public function sendTrendAnalysis($user, $analysis)
    {
        if (!$user->email_notifications || !$user->isPremium()) {
            return;
        }

        try {
            Mail::to($user->email)->send(new TrendAnalysisMail($analysis));
        } catch (\Exception $e) {
            \Log::error('Failed to send trend analysis email: ' . $e->getMessage());
        }
    }

    public function sendWeeklyReport($user, $portfolioData)
    {
        if (!$user->email_notifications) {
            return;
        }

        try {
            Mail::to($user->email)->send(new WeeklyReportMail($user, $portfolioData));
        } catch (\Exception $e) {
            \Log::error('Failed to send weekly report email: ' . $e->getMessage());
        }
    }
}