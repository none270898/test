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
            'sentiment_updated_at' => 'datetime',
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

    public function getTodayTrendAnalyses()
    {
        return $this->trendAnalyses()
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc');
    }

    // DODANE BRAKUJĄCE METODY:

    /**
     * Get trending status based on trending_score
     */
    public function getTrendingStatus(): string
    {
        $score = $this->trending_score ?? 0;

        if ($score >= 80) {
            return 'hot';
        } elseif ($score >= 50) {
            return 'trending';
        } elseif ($score >= 20) {
            return 'moderate';
        } else {
            return 'low';
        }
    }

    /**
     * Get sentiment emoji based on current sentiment
     */
    public function getSentimentEmoji(): string
    {
        $sentiment = $this->current_sentiment ?? 0;

        if ($sentiment > 0.3) {
            return '🚀';
        } elseif ($sentiment > 0.1) {
            return '📈';
        } elseif ($sentiment < -0.3) {
            return '📉';
        } elseif ($sentiment < -0.1) {
            return '🔻';
        } else {
            return '➡️';
        }
    }

    /**
     * Check if has recent sentiment data
     */
    public function hasRecentSentimentData(int $hours = 24): bool
    {
        return $this->sentiment_updated_at &&
            $this->sentiment_updated_at->isAfter(now()->subHours($hours));
    }

    public function watchlists()
    {
        return $this->watchlistedBy();
    }
    public function watchlistUsers()
    {
        return $this->belongsToMany(User::class, 'user_watchlists', 'cryptocurrency_id', 'user_id')
            ->withPivot('notifications_enabled')
            ->withTimestamps();
    }
}
