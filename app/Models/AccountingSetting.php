<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSetting extends Model
{
    protected $fillable = [
        'user_id',
        'is_enabled',
        'currency',
        'date_format',
        'timezone',
        'fiscal_year_start',
        'auto_sync_flowaccount',
        'tax_settings',
        'invoice_settings',
        'expense_settings',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'auto_sync_flowaccount' => 'boolean',
        'tax_settings' => 'array',
        'invoice_settings' => 'array',
        'expense_settings' => 'array',
    ];

    /**
     * Get the user that owns the settings
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if accounting is enabled for this user
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * Get default invoice settings
     */
    public function getInvoiceSettings(): array
    {
        return $this->invoice_settings ?? [
            'default_payment_terms' => 'cash',
            'default_due_days' => 30,
            'auto_number' => true,
            'number_prefix' => 'INV',
            'number_padding' => 5,
            'show_company_logo' => true,
            'default_note' => '',
            'default_terms' => '',
        ];
    }

    /**
     * Get default expense settings
     */
    public function getExpenseSettings(): array
    {
        return $this->expense_settings ?? [
            'auto_number' => true,
            'number_prefix' => 'EXP',
            'number_padding' => 5,
            'require_receipt' => false,
            'default_note' => '',
        ];
    }

    /**
     * Get tax settings
     */
    public function getTaxSettings(): array
    {
        return $this->tax_settings ?? [
            'default_tax_rate' => 7.00,
            'tax_inclusive' => false,
            'withholding_tax' => false,
            'default_withholding_rate' => 3.00,
        ];
    }
}
