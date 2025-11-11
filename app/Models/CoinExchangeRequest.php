<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinExchangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exchange_rate_id',
        'coins_amount',
        'money_amount',
        'exchange_rate',
        'status',
        'admin_note',
        'approved_by',
        'approved_at',
        'completed_at',
        'wallet_transaction_id',
    ];

    protected $casts = [
        'coins_amount' => 'decimal:2',
        'money_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the exchange rate
     */
    public function exchangeRate()
    {
        return $this->belongsTo(CoinExchangeRate::class, 'exchange_rate_id');
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get wallet transaction
     */
    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Approve request
     */
    public function approve($adminId, $note = null)
    {
        $this->status = 'approved';
        $this->approved_by = $adminId;
        $this->approved_at = now();
        $this->admin_note = $note;
        $this->save();

        return $this->processExchange();
    }

    /**
     * Reject request
     */
    public function reject($adminId, $note)
    {
        $this->status = 'rejected';
        $this->approved_by = $adminId;
        $this->approved_at = now();
        $this->admin_note = $note;
        $this->save();

        // Refund coins
        $videoCoin = VideoCoin::where('user_id', $this->user_id)->first();
        if ($videoCoin) {
            $videoCoin->addCoins(
                $this->coins_amount,
                'adjustment',
                'CoinExchangeRequest',
                $this->id,
                'Exchange request rejected - refund'
            );
        }
    }

    /**
     * Process the exchange (transfer to wallet)
     */
    protected function processExchange()
    {
        // Get or create user's wallet
        $wallet = Wallet::firstOrCreate(['user_id' => $this->user_id]);

        // Add money to wallet
        $walletTransaction = WalletTransaction::create([
            'user_id' => $this->user_id,
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => $this->money_amount,
            'balance_before' => $wallet->balance,
            'balance_after' => $wallet->balance + $this->money_amount,
            'description' => "Exchanged {$this->coins_amount} video coins to money",
            'reference_type' => 'CoinExchangeRequest',
            'reference_id' => $this->id,
            'status' => 'completed',
        ]);

        $wallet->balance += $this->money_amount;
        $wallet->total_income += $this->money_amount;
        $wallet->save();

        // Update request
        $this->status = 'completed';
        $this->completed_at = now();
        $this->wallet_transaction_id = $walletTransaction->id;
        $this->save();

        return $walletTransaction;
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
