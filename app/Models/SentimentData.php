<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentimentData extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'content',
        'url',
        'sentiment_score',
        'keywords',
        'language',
        'published_at',
    ];

    protected $casts = [
        'sentiment_score' => 'decimal:2',
        'keywords' => 'array',
        'published_at' => 'datetime',
    ];

    public function getSentimentLabelAttribute()
    {
        if ($this->sentiment_score > 0.3) return 'positive';
        if ($this->sentiment_score < -0.3) return 'negative';
        return 'neutral';
    }
}