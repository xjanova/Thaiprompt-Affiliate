<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingJournalEntry extends Model
{
    protected $fillable = [
        'user_id',
        'journal_number',
        'journal_date',
        'journalable_type',
        'journalable_id',
        'chart_account_id',
        'type',
        'amount',
        'description',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function journalable(): MorphTo
    {
        return $this->morphTo();
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingChartOfAccount::class, 'chart_account_id');
    }
}
