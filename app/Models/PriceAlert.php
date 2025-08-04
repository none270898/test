<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cryptocurrency_id',
        'alert_type',
        'target_price',
        'currency',
        'is_active',
        'email_notification',
        'push_notification',
        'triggered_at',
    ];

    protected $casts = [
        'target_price' => 'decimal:8',
        'is_active' => 'boolean',
        'email_notification' => 'boolean',
        'push_notification' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cryptocurrency()
    {
        return $this->belongsTo(Cryptocurrency::class);
    }

    public function shouldTrigger()
    {
        if (!$this->is_active || $this->triggered_at) {
            return false;
        }

        $currentPrice = $this->currency === 'PLN' 
            ? $this->cryptocurrency->current_price_pln 
            : $this->cryptocurrency->current_price_usd;

        return ($this->alert_type === 'above' && $currentPrice >= $this->target_price) ||
               ($this->alert_type === 'below' && $currentPrice <= $this->target_price);
    }
}