<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cryptocurrency extends Model
{
    use HasFactory;

    protected $fillable = [
        'coingecko_id',
        'symbol',
        'name',
        'image',
        'current_price_usd',
        'current_price_pln',
        'market_cap',
        'market_cap_rank',
        'price_change_24h',
        'price_change_percentage_24h',
        'last_updated',
    ];

    protected $casts = [
        'current_price_usd' => 'decimal:8',
        'current_price_pln' => 'decimal:8',
        'market_cap' => 'decimal:2',
        'price_change_24h' => 'decimal:4',
        'price_change_percentage_24h' => 'decimal:4',
        'last_updated' => 'datetime',
    ];

    public function portfolios()
    {
        return $this->hasMany(Portfolio::class);
    }

    public function priceAlerts()
    {
        return $this->hasMany(PriceAlert::class);
    }

    public function trendAnalyses()
    {
        return $this->hasMany(TrendAnalysis::class);
    }

    public function getLatestTrendAnalysis()
    {
        return $this->trendAnalyses()->latest()->first();
    }
}