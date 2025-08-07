<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrendAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'cryptocurrency_id',
        'sentiment_avg',
        'mention_count',
        'trend_direction',
        'confidence_score',
        'analysis_period_start',
        'analysis_period_end',
    ];

    protected function casts(): array
    {
        return [
            'sentiment_avg' => 'decimal:2',
            'analysis_period_start' => 'datetime',
            'analysis_period_end' => 'datetime',
        ];
    }

    public function cryptocurrency()
    {
        return $this->belongsTo(Cryptocurrency::class);
    }

    public function getTrendEmoji(): string
    {
        return match($this->trend_direction) {
            'up' => '📈',
            'down' => '📉',
            default => '➡️',
        };
    }
}