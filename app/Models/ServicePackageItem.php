<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ServicePackageItem Model - รายการในแพคเกจ
 *
 * เก็บรายการบริการหรือสินค้าที่อยู่ในแพคเกจ
 * รองรับ polymorphic relationship สำหรับ Service หรือ Product
 *
 * @property int $id
 * @property int $package_id
 * @property string $item_type 'service' หรือ 'product'
 * @property int $item_id ID ของ service หรือ product
 * @property int $quantity จำนวน
 * @property float $price ราคา
 * @property string|null $notes หมายเหตุ
 */
class ServicePackageItem extends Model
{
    use HasFactory;

    protected $table = 'service_package_items';

    protected $fillable = [
        'package_id',
        'item_type',
        'item_id',
        'quantity',
        'price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    //===========================================
    // Relationships
    //===========================================

    /**
     * แพคเกจที่รายการนี้เป็นของ
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }

    /**
     * รายการที่เป็น Service หรือ Product (Polymorphic)
     *
     * แม้ว่าจะไม่ได้ใช้ morphTo() แต่เราจะใช้ manual relationship
     */
    public function item()
    {
        if ($this->item_type === 'service') {
            return $this->service();
        } elseif ($this->item_type === 'product') {
            return $this->product();
        }
        return null;
    }

    /**
     * ความสัมพันธ์กับ Service
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'item_id');
    }

    /**
     * ความสัมพันธ์กับ Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: เฉพาะรายการที่เป็น service
     */
    public function scopeServices($query)
    {
        return $query->where('item_type', 'service');
    }

    /**
     * Scope: เฉพาะรายการที่เป็น product
     */
    public function scopeProducts($query)
    {
        return $query->where('item_type', 'product');
    }

    //===========================================
    // Methods
    //===========================================

    /**
     * ดึงข้อมูลรายการจริง (Service หรือ Product)
     */
    public function getItemModel()
    {
        if ($this->item_type === 'service') {
            return Service::find($this->item_id);
        } elseif ($this->item_type === 'product') {
            return Product::find($this->item_id);
        }
        return null;
    }

    /**
     * ชื่อของรายการ
     */
    public function getItemName(): string
    {
        $item = $this->getItemModel();
        return $item ? $item->name : 'ไม่พบข้อมูล';
    }

    /**
     * ราคารวม (price × quantity)
     */
    public function getTotalPrice(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * คำนวณราคาจากรายการจริง
     */
    public function calculatePriceFromItem(): float
    {
        $item = $this->getItemModel();

        if (!$item) {
            return 0;
        }

        if ($this->item_type === 'service' && isset($item->base_price)) {
            return $item->base_price * $this->quantity;
        } elseif ($this->item_type === 'product' && isset($item->price)) {
            return $item->price * $this->quantity;
        }

        return 0;
    }

    /**
     * อัพเดทราคาจากรายการจริง
     */
    public function syncPriceFromItem(): void
    {
        $this->price = $this->calculatePriceFromItem();
        $this->save();
    }

    /**
     * ตรวจสอบว่ารายการยังมีอยู่หรือไม่
     */
    public function itemExists(): bool
    {
        return $this->getItemModel() !== null;
    }

    /**
     * แสดงประเภทรายการเป็นภาษาไทย
     */
    public function getItemTypeText(): string
    {
        return match ($this->item_type) {
            'service' => 'บริการ',
            'product' => 'สินค้า',
            default => 'ไม่ระบุ',
        };
    }
}
