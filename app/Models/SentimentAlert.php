<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SentimentAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cryptocurrency_id',
        'trigger_type',
        'threshold_value',
        'mention_threshold',
        'is_active',
        'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'threshold_value' => 'decimal:2',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cryptocurrency()
    {
        return $this->belongsTo(Cryptocurrency::class);
    }

    public function shouldTrigger(float $currentSentiment, int $currentMentions): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Don't trigger more than once per day
        if ($this->last_triggered_at && $this->last_triggered_at->isToday()) {
            return false;
        }

        return match($this->trigger_type) {
            'sentiment_spike' => $currentSentiment >= $this->threshold_value,
            'sentiment_drop' => $currentSentiment <= $this->threshold_value,
            'mention_spike' => $currentMentions >= $this->mention_threshold,
            default => false,
        };
    }
}