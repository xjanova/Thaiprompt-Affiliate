<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServiceBookingItem Model - รายการย่อยในการจอง
 *
 * เก็บรายละเอียดรายการย่อยแต่ละรายการในการจอง
 * เช่น บริการหลัก, สินค้าเสริม, ออฟชั่น, ค่าเดินทาง
 *
 * @property int $id
 * @property int $booking_id
 * @property string $item_type ประเภท: service, product, option, distance_fee
 * @property int|null $item_id ID ของรายการ
 * @property string $name ชื่อรายการ
 * @property int $quantity จำนวน
 * @property float $unit_price ราคาต่อหน่วย
 * @property float $total_price ราคารวม
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 */
class ServiceBookingItem extends Model
{
    use HasFactory;

    protected $table = 'service_booking_items';

    protected $fillable = [
        'booking_id',
        'item_type',
        'item_id',
        'name',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'metadata' => 'array',
    ];

    //===========================================
    // Lifecycle Hooks
    //===========================================

    protected static function boot()
    {
        parent::boot();

        // คำนวณ subtotal อัตโนมัติ
        static::saving(function ($item) {
            if ($item->isDirty(['unit_price', 'quantity'])) {
                $item->subtotal = $item->unit_price * $item->quantity;
            }
        });
    }

    //===========================================
    // Relationships
    //===========================================

    /**
     * การจองที่รายการนี้เป็นของ
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'booking_id');
    }

    /**
     * บริการ (ถ้ารายการนี้เป็น service)
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'item_id');
    }

    /**
     * สินค้า (ถ้ารายการนี้เป็น product)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: รายการเฉพาะประเภท
     */
    public function scopeType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    /**
     * Scope: รายการบริการ
     */
    public function scopeServices($query)
    {
        return $query->where('item_type', 'service');
    }

    /**
     * Scope: รายการสินค้า
     */
    public function scopeProducts($query)
    {
        return $query->where('item_type', 'product');
    }

    /**
     * Scope: รายการออฟชั่น
     */
    public function scopeOptions($query)
    {
        return $query->where('item_type', 'option');
    }

    //===========================================
    // Methods
    //===========================================

    /**
     * ประเภทรายการเป็นภาษาไทย
     */
    public function getTypeTextAttribute(): string
    {
        return match ($this->item_type) {
            'service' => 'บริการ',
            'product' => 'สินค้า',
            'option' => 'ออฟชั่นเพิ่มเติม',
            'distance_fee' => 'ค่าเดินทาง',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * คำนวณราคารวมใหม่
     */
    public function recalculateTotal(): void
    {
        $this->subtotal = $this->unit_price * $this->quantity;
        $this->save();
    }

    /**
     * ราคารวม (alias สำหรับ subtotal)
     */
    public function getTotalPriceAttribute(): float
    {
        return $this->subtotal;
    }

    /**
     * ดึงรายการจริง (Service หรือ Product)
     */
    public function getItemModel()
    {
        if ($this->item_type === 'service' && $this->item_id) {
            return Service::find($this->item_id);
        }

        if ($this->item_type === 'product' && $this->item_id) {
            return Product::find($this->item_id);
        }

        return null;
    }
}
