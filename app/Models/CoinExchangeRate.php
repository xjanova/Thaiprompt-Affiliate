<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'coins_amount',
        'money_amount',
        'rate',
        'min_level',
        'min_coins',
        'max_coins_per_day',
        'max_coins_per_month',
        'is_active',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'coins_amount' => 'decimal:2',
        'money_amount' => 'decimal:2',
        'rate' => 'decimal:4',
        'min_level' => 'integer',
        'min_coins' => 'decimal:2',
        'max_coins_per_day' => 'decimal:2',
        'max_coins_per_month' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
    ];

    /**
     * Get exchange requests using this rate
     */
    public function exchangeRequests()
    {
        return $this->hasMany(CoinExchangeRequest::class, 'exchange_rate_id');
    }

    /**
     * Calculate money amount for given coins
     */
    public function calculateMoney($coinsAmount)
    {
        return $coinsAmount * $this->rate;
    }

    /**
     * Check if user can exchange
     */
    public function canUserExchange($user, $coinsAmount)
    {
        // Check minimum coins
        if ($coinsAmount < $this->min_coins) {
            return ['can' => false, 'reason' => 'Below minimum exchange amount'];
        }

        // Check user level
        $userLevel = UserVideoLevel::where('user_id', $user->id)->first();
        if (!$userLevel || $userLevel->currentLevel->level < $this->min_level) {
            return ['can' => false, 'reason' => 'User level too low'];
        }

        // Check daily limit
        if ($this->max_coins_per_day) {
            $todayExchanged = CoinExchangeRequest::where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('coins_amount');

            if ($todayExchanged + $coinsAmount > $this->max_coins_per_day) {
                return ['can' => false, 'reason' => 'Exceeds daily limit'];
            }
        }

        // Check monthly limit
        if ($this->max_coins_per_month) {
            $monthlyExchanged = CoinExchangeRequest::where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('coins_amount');

            if ($monthlyExchanged + $coinsAmount > $this->max_coins_per_month) {
                return ['can' => false, 'reason' => 'Exceeds monthly limit'];
            }
        }

        return ['can' => true];
    }

    /**
     * Scope for active rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', now());
            });
    }
}
