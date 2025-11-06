<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class AccountingInvoice extends Model
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
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance',
        'note',
        'terms',
        'flowaccount_id',
        'synced_at',
    ];

    protected $casts = [
        'document_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
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

        static::creating(function ($invoice) {
            if (empty($invoice->document_number)) {
                $invoice->document_number = self::generateDocumentNumber($invoice->user_id, $invoice->document_type);
            }

            // Set default dates
            if (empty($invoice->document_date)) {
                $invoice->document_date = now();
            }

            if (empty($invoice->due_date) && $invoice->contact) {
                $invoice->due_date = now()->addDays($invoice->contact->credit_days ?? 30);
            }
        });

        static::updating(function ($invoice) {
            // Update balance
            $invoice->balance = $invoice->total_amount - $invoice->paid_amount;

            // Update status based on payment
            if ($invoice->balance <= 0) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partial';
            } elseif ($invoice->due_date && $invoice->due_date->isPast() && $invoice->balance > 0) {
                $invoice->status = 'overdue';
            }
        });
    }

    /**
     * Generate unique document number
     */
    public static function generateDocumentNumber($userId, $documentType): string
    {
        $prefixes = [
            'invoice' => 'INV',
            'tax_invoice' => 'TAX',
            'receipt' => 'REC',
            'quotation' => 'QUO',
        ];

        $prefix = $prefixes[$documentType] ?? 'DOC';
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
     * Get the user that owns the invoice
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
     * Get the contact (customer)
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(AccountingContact::class, 'contact_id');
    }

    /**
     * Get invoice items
     */
    public function items(): HasMany
    {
        return $this->hasMany(AccountingInvoiceItem::class, 'invoice_id');
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
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->balance = $this->total_amount - $this->paid_amount;
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' ||
               ($this->due_date && $this->due_date->isPast() && $this->balance > 0);
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->due_date || !$this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Record payment
     */
    public function recordPayment(float $amount, array $paymentData = []): AccountingPayment
    {
        $payment = $this->payments()->create(array_merge([
            'user_id' => $this->user_id,
            'payment_type' => 'income',
            'payment_number' => AccountingPayment::generatePaymentNumber($this->user_id, 'income'),
            'payment_date' => now(),
            'amount' => $amount,
        ], $paymentData));

        $this->paid_amount += $amount;
        $this->save();

        return $payment;
    }
}
