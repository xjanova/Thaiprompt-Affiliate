<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingChartOfAccount extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'name',
        'name_eng',
        'type',
        'sub_type',
        'parent_id',
        'level',
        'is_active',
        'is_system',
        'description',
        'opening_balance',
        'current_balance',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(AccountingJournalEntry::class, 'chart_account_id');
    }

    public function getFullCodeAttribute(): string
    {
        return $this->code . ' - ' . $this->name;
    }

    public function updateBalance(float $amount, string $type): void
    {
        if ($type === 'debit') {
            if (in_array($this->type, ['asset', 'expense'])) {
                $this->current_balance += $amount;
            } else {
                $this->current_balance -= $amount;
            }
        } else {
            if (in_array($this->type, ['liability', 'equity', 'revenue'])) {
                $this->current_balance += $amount;
            } else {
                $this->current_balance -= $amount;
            }
        }

        $this->save();
    }
}
