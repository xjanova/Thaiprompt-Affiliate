<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TPIXTokenBalance extends Model
{
    protected $table = 'tpix_token_balances';

    protected $fillable = [
        'token_id',
        'user_id',
        'wallet_address',
        'balance',
        'locked_balance',
        'staked_balance',
        'available_balance',
        'total_received',
        'total_sent',
        'transfers_count',
        'first_received_at',
        'last_activity_at',
        'is_frozen',
        'freeze_reason',
    ];

    protected $casts = [
        'is_frozen' => 'boolean',
        'first_received_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function token()
    {
        return $this->belongsTo(TPIXToken::class, 'token_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('balance', '>', 0);
    }

    public function scopeNotFrozen($query)
    {
        return $query->where('is_frozen', false);
    }
}
