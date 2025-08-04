<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cryptocurrency_id',
        'amount',
        'average_buy_price',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'average_buy_price' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cryptocurrency()
    {
        return $this->belongsTo(Cryptocurrency::class);
    }

    public function getCurrentValueAttribute()
    {
        return $this->amount * $this->cryptocurrency->current_price_pln;
    }

    public function getProfitLossAttribute()
    {
        if (!$this->average_buy_price) return 0;
        
        $currentValue = $this->getCurrentValueAttribute();
        $investedValue = $this->amount * $this->average_buy_price;
        
        return $currentValue - $investedValue;
    }

    public function getProfitLossPercentageAttribute()
    {
        if (!$this->average_buy_price) return 0;
        
        $profitLoss = $this->getProfitLossAttribute();
        $investedValue = $this->amount * $this->average_buy_price;
        
        return $investedValue > 0 ? ($profitLoss / $investedValue) * 100 : 0;
    }
}