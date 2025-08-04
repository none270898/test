<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrendAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'cryptocurrency_id',
        'sentiment_avg',
        'mention_count',
        'trend_direction',
        'confidence_score',
        'source_breakdown',
        'analysis_period_start',
        'analysis_period_end',
    ];

    protected $casts = [
        'sentiment_avg' => 'decimal:2',
        'source_breakdown' => 'array',
        'analysis_period_start' => 'datetime',
        'analysis_period_end' => 'datetime',
    ];

    public function cryptocurrency()
    {
        return $this->belongsTo(Cryptocurrency::class);
    }

    public function getTrendEmoji()
    {
        return match($this->trend_direction) {
            'up' => '📈',
            'down' => '📉',
            default => '➡️'
        };
    }

    public function getConfidenceLabel()
    {
        if ($this->confidence_score >= 80) return 'Bardzo wysoka';
        if ($this->confidence_score >= 60) return 'Wysoka';
        if ($this->confidence_score >= 40) return 'Średnia';
        return 'Niska';
    }
}