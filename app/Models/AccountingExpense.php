<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AccountingExpense extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'contact_id',
        'document_type',
        'document_number',
        'reference_number',
        'document_date',
        'due_date',
        'status',
        'category_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance',
        'note',
        'receipt_image',
        'flowaccount_id',
        'synced_at',
    ];

    protected $casts = [
        'document_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'synced_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (empty($expense->document_number)) {
                $expense->document_number = self::generateDocumentNumber($expense->user_id, $expense->document_type);
            }

            if (empty($expense->document_date)) {
                $expense->document_date = now();
            }
        });

        static::updating(function ($expense) {
            $expense->balance = $expense->total_amount - $expense->paid_amount;

            if ($expense->balance <= 0) {
                $expense->status = 'paid';
            } elseif ($expense->paid_amount > 0) {
                $expense->status = 'partial';
            }
        });
    }

    /**
     * Generate unique document number
     */
    public static function generateDocumentNumber($userId, $documentType): string
    {
        $prefixes = [
            'expense' => 'EXP',
            'purchase' => 'PUR',
            'bill' => 'BILL',
        ];

        $prefix = $prefixes[$documentType] ?? 'EXP';
        $year = date('y');
        $month = date('m');

        $count = self::where('user_id', $userId)
            ->where('document_type', $documentType)
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count() + 1;

        return $prefix . $year . $month . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AccountingCompany::class, 'company_id');
    }

    /**
     * Get the contact (vendor)
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(AccountingContact::class, 'contact_id');
    }

    /**
     * Get the category (chart of account)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountingChartOfAccount::class, 'category_id');
    }

    /**
     * Get expense items
     */
    public function items(): HasMany
    {
        return $this->hasMany(AccountingExpenseItem::class, 'expense_id');
    }

    /**
     * Get payments
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(AccountingPayment::class, 'payable');
    }

    /**
     * Get journal entries
     */
    public function journalEntries(): MorphMany
    {
        return $this->morphMany(AccountingJournalEntry::class, 'journalable');
    }

    /**
     * Get activity logs
     */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(AccountingActivityLog::class, 'loggable');
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total_amount');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total_amount = $this->subtotal + $this->tax_amount;
        $this->balance = $this->total_amount - $this->paid_amount;
    }

    /**
     * Get receipt URL
     */
    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_image ? asset('storage/' . $this->receipt_image) : null;
    }

    /**
     * Record payment
     */
    public function recordPayment(float $amount, array $paymentData = []): AccountingPayment
    {
        $payment = $this->payments()->create(array_merge([
            'user_id' => $this->user_id,
            'payment_type' => 'expense',
            'payment_number' => AccountingPayment::generatePaymentNumber($this->user_id, 'expense'),
            'payment_date' => now(),
            'amount' => $amount,
        ], $paymentData));

        $this->paid_amount += $amount;
        $this->save();

        return $payment;
    }
}
