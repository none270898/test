<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PortfolioHolding extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cryptocurrency_id',
        'amount',
        'average_buy_price',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'average_buy_price' => 'decimal:8',
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

    public function getCurrentValuePln(): float
    {
        return $this->amount * $this->cryptocurrency->current_price_pln;
    }

    public function getProfitLossPln(): float
    {
        if (!$this->average_buy_price) return 0;
        
        $currentValue = $this->getCurrentValuePln();
        $investedValue = $this->amount * $this->average_buy_price;
        
        return $currentValue - $investedValue;
    }

    public function getProfitLossPercentage(): float
    {
        if (!$this->average_buy_price) return 0;
        
        $profitLoss = $this->getProfitLossPln();
        $investedValue = $this->amount * $this->average_buy_price;
        
        return $investedValue > 0 ? ($profitLoss / $investedValue) * 100 : 0;
    }
}
