<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingExpenseItem extends Model
{
    protected $fillable = [
        'expense_id',
        'product_id',
        'chart_account_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $subtotal = $item->quantity * $item->unit_price;
            $item->tax_amount = $subtotal * ($item->tax_rate / 100);
            $item->total_amount = $subtotal + $item->tax_amount;
        });

        static::saved(function ($item) {
            if ($item->expense) {
                $item->expense->calculateTotals();
                $item->expense->save();
            }
        });

        static::deleted(function ($item) {
            if ($item->expense) {
                $item->expense->calculateTotals();
                $item->expense->save();
            }
        });
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(AccountingExpense::class, 'expense_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(AccountingProduct::class, 'product_id');
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingChartOfAccount::class, 'chart_account_id');
    }
}
