<?php
// app/Models/User.php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'premium',
        'premium_expires_at',
        'alerts_enabled',
        'email_notifications',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'premium_expires_at' => 'datetime',
            'password' => 'hashed',
            'premium' => 'boolean',
            'alerts_enabled' => 'boolean',
            'email_notifications' => 'boolean',
        ];
    }

    public function portfolioHoldings()
    {
        return $this->hasMany(PortfolioHolding::class);
    }

    public function priceAlerts()
    {
        return $this->hasMany(PriceAlert::class);
    }

    public function watchlist()
    {
        return $this->hasMany(UserWatchlist::class);
    }

    public function sentimentAlerts()
    {
        return $this->hasMany(SentimentAlert::class);
    }

    public function getWatchlistCryptos()
    {
        return $this->watchlist()
            ->with('cryptocurrency')
            ->get()
            ->pluck('cryptocurrency');
    }

    public function isPremium(): bool
    {
        return $this->premium && 
               ($this->premium_expires_at === null || $this->premium_expires_at->isFuture());
    }

    public function getPortfolioValuePln(): float
    {
        return $this->portfolioHoldings()
            ->with('cryptocurrency')
            ->get()
            ->sum(function ($holding) {
                return $holding->amount * $holding->cryptocurrency->current_price_pln;
            });
    }

    // Custom email notifications
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}