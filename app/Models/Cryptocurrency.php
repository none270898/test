<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'price_change_24h',
        'last_updated',
        // Sentiment fields - IMPORTANT for updates
        'daily_mentions',
        'current_sentiment',
        'sentiment_change_24h',
        'trending_score',
        'sentiment_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'current_price_usd' => 'decimal:8',
            'current_price_pln' => 'decimal:8',
            'price_change_24h' => 'decimal:2',
            'current_sentiment' => 'decimal:2',
            'sentiment_change_24h' => 'decimal:2',
            'last_updated' => 'datetime',
            'sentiment_updated_at' => 'datetime', // DODANE
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

    public function trendAnalyses()
    {
        return $this->hasMany(TrendAnalysis::class);
    }

    public function watchlistedBy()
    {
        return $this->hasMany(UserWatchlist::class);
    }

    public function isWatchlistedBy(User $user): bool
    {
        return $this->watchlistedBy()->where('user_id', $user->id)->exists();
    }

    public function getLatestTrendAnalysis()
    {
        return $this->trendAnalyses()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function getTodayTrendAnalysis()
    {
        return $this->trendAnalyses()
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->first();
    }
}