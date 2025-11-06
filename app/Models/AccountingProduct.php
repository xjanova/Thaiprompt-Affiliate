<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AccountingProduct extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'type',
        'name',
        'name_eng',
        'description',
        'unit',
        'price',
        'cost',
        'income_account_id',
        'expense_account_id',
        'is_taxable',
        'tax_rate',
        'is_active',
        'flowaccount_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->code)) {
                $product->code = self::generateCode($product->user_id, $product->type);
            }
        });
    }

    /**
     * Generate unique product code
     */
    public static function generateCode($userId, $type): string
    {
        $prefix = $type === 'product' ? 'PRD' : 'SRV';
        $count = self::where('user_id', $userId)->where('type', $type)->count() + 1;

        return $prefix . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the user that owns the product
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get income account
     */
    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingChartOfAccount::class, 'income_account_id');
    }

    /**
     * Get expense account
     */
    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingChartOfAccount::class, 'expense_account_id');
    }

    /**
     * Calculate price with tax
     */
    public function getPriceWithTaxAttribute(): float
    {
        if (!$this->is_taxable) {
            return $this->price;
        }

        return $this->price * (1 + ($this->tax_rate / 100));
    }

    /**
     * Get gross margin
     */
    public function getGrossMarginAttribute(): float
    {
        if ($this->price == 0) {
            return 0;
        }

        return (($this->price - $this->cost) / $this->price) * 100;
    }
}
