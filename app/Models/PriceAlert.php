<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PriceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cryptocurrency_id',
        'type',
        'target_price',
        'currency',
        'is_active',
        'triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'target_price' => 'decimal:8',
            'is_active' => 'boolean',
            'triggered_at' => 'datetime',
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

    public function shouldTrigger(): bool
    {
        if (!$this->is_active || $this->triggered_at) {
            return false;
        }

        $currentPrice = $this->currency === 'PLN' 
            ? $this->cryptocurrency->current_price_pln 
            : $this->cryptocurrency->current_price_usd;

        return match($this->type) {
            'above' => $currentPrice >= $this->target_price,
            'below' => $currentPrice <= $this->target_price,
            default => false,
        };
    }
}
