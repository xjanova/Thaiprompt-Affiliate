<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NfcCardTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'nfc_card_id',
        'user_id',
        'transaction_type',
        'amount',
        'status',
        'terminal_id',
        'ip_address',
        'user_agent',
        'error_message',
        'meta_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta_data' => 'array',
    ];

    // Relationships
    public function nfcCard()
    {
        return $this->belongsTo(NfcCard::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePayments($query)
    {
        return $query->where('transaction_type', 'payment');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
