<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoCoin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'lifetime_earned',
        'lifetime_spent',
        'lifetime_exchanged',
        'last_earned_at',
        'last_spent_at',
        'last_exchanged_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'lifetime_earned' => 'decimal:2',
        'lifetime_spent' => 'decimal:2',
        'lifetime_exchanged' => 'decimal:2',
        'last_earned_at' => 'datetime',
        'last_spent_at' => 'datetime',
        'last_exchanged_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions
     */
    public function transactions()
    {
        return $this->hasMany(VideoCoinTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Add coins
     */
    public function addCoins($amount, $type, $referenceType = null, $referenceId = null, $description = null)
    {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->lifetime_earned += $amount;
        $this->last_earned_at = now();
        $this->save();

        // Create transaction record
        VideoCoinTransaction::create([
            'user_id' => $this->user_id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
        ]);

        return $this;
    }

    /**
     * Deduct coins
     */
    public function deductCoins($amount, $type, $referenceType = null, $referenceId = null, $description = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient coins balance');
        }

        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->lifetime_spent += $amount;
        $this->last_spent_at = now();
        $this->save();

        // Create transaction record
        VideoCoinTransaction::create([
            'user_id' => $this->user_id,
            'type' => $type,
            'amount' => -$amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
        ]);

        return $this;
    }

    /**
     * Exchange coins to money
     */
    public function exchangeToMoney($amount, $exchangeRateId)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient coins balance');
        }

        $this->deductCoins($amount, 'spent_exchange', 'CoinExchangeRate', $exchangeRateId, 'Exchange coins to money');

        $this->lifetime_exchanged += $amount;
        $this->last_exchanged_at = now();
        $this->save();

        return $this;
    }

    /**
     * Get balance formatted
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 2);
    }
}
