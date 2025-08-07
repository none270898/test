<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SentimentData extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'content',
        'sentiment_score',
        'keywords',
        'source_url',
        'author',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'sentiment_score' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }
}