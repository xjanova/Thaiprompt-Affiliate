<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_transaction_id',
        'product_id',
        'product_name',
        'product_sku',
        'product_barcode',
        'product_description',
        'product_image',
        'unit_price',
        'original_price',
        'quantity',
        'discount_amount',
        'discount_percentage',
        'subtotal',
        'total',
        'tax_amount',
        'tax_percentage',
        'is_tax_inclusive',
        'variant_attributes',
        'notes',
        'special_instructions',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'is_tax_inclusive' => 'boolean',
        'variant_attributes' => 'array',
    ];

    /**
     * Relationships
     */
    public function posTransaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Helper Methods
     */
    public function calculateSubtotal(): float
    {
        return round($this->unit_price * $this->quantity, 2);
    }

    public function calculateDiscount(): float
    {
        $subtotal = $this->calculateSubtotal();

        if ($this->discount_amount > 0) {
            return round($this->discount_amount, 2);
        }

        if ($this->discount_percentage > 0) {
            return round($subtotal * ($this->discount_percentage / 100), 2);
        }

        return 0;
    }

    public function calculateTotal(): float
    {
        $subtotal = $this->calculateSubtotal();
        $discount = $this->calculateDiscount();
        $afterDiscount = $subtotal - $discount;

        if ($this->is_tax_inclusive) {
            return round($afterDiscount, 2);
        }

        $taxAmount = $afterDiscount * ($this->tax_percentage / 100);

        return round($afterDiscount + $taxAmount, 2);
    }

    public function calculateTaxAmount(): float
    {
        $subtotal = $this->calculateSubtotal();
        $discount = $this->calculateDiscount();
        $afterDiscount = $subtotal - $discount;

        if ($this->is_tax_inclusive) {
            // Extract tax from inclusive price
            $divisor = 1 + ($this->tax_percentage / 100);

            return round($afterDiscount - ($afterDiscount / $divisor), 2);
        }

        return round($afterDiscount * ($this->tax_percentage / 100), 2);
    }

    public function updateStock(): void
    {
        if ($this->product && $this->product->track_inventory) {
            $this->product->decrement('stock_quantity', $this->quantity);

            // Update stock status — enum ของ products.stock_status มีแค่ in_stock|out_of_stock|on_backorder
            // (เดิมเขียน 'low_stock' ซึ่งไม่อยู่ใน enum → บันทึกการขายพังเมื่อสต็อกเหลือน้อย)
            if ($this->product->stock_quantity <= 0) {
                $this->product->update(['stock_status' => 'out_of_stock']);
            }
        }
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Auto-calculate values if not set
            if (empty($item->subtotal)) {
                $item->subtotal = $item->calculateSubtotal();
            }
            if (empty($item->discount_amount) && empty($item->discount_percentage)) {
                $item->discount_amount = 0;
            }
            if (empty($item->total)) {
                $item->total = $item->calculateTotal();
            }
            if (empty($item->tax_amount)) {
                $item->tax_amount = $item->calculateTaxAmount();
            }
        });

        // (2026-09-25) เอา hook ตัดสต็อกอัตโนมัติตอน created ออก — ทุกจุดที่สร้างรายการ
        // (SellerPosController, Pos\PosApiController, PosOfflineQueue) ตัดสต็อกเองอยู่แล้ว
        // เดิมจึงโดนตัดสต็อก 2 เท่าทุกการขาย · ถ้าต้องการให้โมเดลตัดเอง เรียก $item->updateStock() ตรง ๆ
    }
}
