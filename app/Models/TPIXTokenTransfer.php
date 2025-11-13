<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TPIXTokenTransfer extends Model
{
    protected $table = 'tpix_token_transfers';

    protected $fillable = [
        'token_id', 'tx_hash', 'from_address', 'to_address', 'amount',
        'from_user_id', 'to_user_id', 'block_number', 'block_timestamp',
        'confirmations', 'gas_used', 'gas_price', 'transfer_type', 'status', 'notes',
    ];

    protected $casts = [
        'block_timestamp' => 'datetime',
    ];

    public function token()
    {
        return $this->belongsTo(TPIXToken::class, 'token_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
