<?php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'subscription_plan',
        'subscription_expires_at',
        'onesignal_player_id',
        'email_notifications',
        'push_notifications',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'password' => 'hashed',
    ];

    public function portfolios()
    {
        return $this->hasMany(Portfolio::class);
    }

    public function priceAlerts()
    {
        return $this->hasMany(PriceAlert::class);
    }

    public function isPremium()
    {
        return $this->subscription_plan === 'premium' && 
               $this->subscription_expires_at && 
               $this->subscription_expires_at->isFuture();
    }

    public function getTotalPortfolioValueAttribute()
    {
        return $this->portfolios->sum(function ($portfolio) {
            return $portfolio->amount * $portfolio->cryptocurrency->current_price_pln;
        });
    }
}