<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingBankAccount extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_name',
        'account_number',
        'branch',
        'account_type',
        'opening_balance',
        'current_balance',
        'chart_account_id',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingChartOfAccount::class, 'chart_account_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->bank_name} - {$this->account_number}";
    }
}
