<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * CoinPurchase Model
 *
 * ประวัติการซื้อสินค้าด้วย Video Coins
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property int $quantity
 * @property float $price_coins ราคาต่อชิ้น
 * @property float $total_coins ราคารวม
 * @property float $discount_coins ส่วนลด
 * @property string $product_name ชื่อสินค้า
 * @property string $product_type ประเภทสินค้า
 * @property float|null $product_value มูลค่าสินค้า
 * @property string|null $product_value_unit หน่วยมูลค่า
 * @property string $order_number เลขที่ใบสั่งซื้อ
 * @property string|null $redemption_code รหัสใช้งาน
 * @property string|null $tracking_number เลขพัสดุ
 * @property string $status สถานะ
 * @property string|null $status_note หมายเหตุสถานะ
 * @property Carbon|null $used_at วันที่ใช้งาน
 * @property string|null $used_note หมายเหตุการใช้งาน
 * @property string|null $used_location สถานที่ใช้งาน
 * @property array|null $used_data ข้อมูลการใช้งาน
 * @property Carbon|null $expires_at วันหมดอายุ
 * @property array|null $shipping_address ที่อยู่จัดส่ง
 * @property Carbon|null $shipped_at วันที่จัดส่ง
 * @property Carbon|null $delivered_at วันที่ได้รับสินค้า
 * @property array|null $meta_data ข้อมูลอื่นๆ
 * @property string|null $admin_note หมายเหตุจาก Admin
 * @property string|null $user_note หมายเหตุจากผู้ใช้
 * @property Carbon|null $refunded_at
 * @property float|null $refunded_coins
 * @property string|null $refund_reason
 * @property int|null $refunded_by
 * @property int|null $processed_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class CoinPurchase extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'coin_purchases';

    /**
     * สถานะการซื้อ
     */
    public const STATUSES = [
        'pending' => [
            'name' => 'รอดำเนินการ',
            'color' => 'yellow',
            'icon' => '⏳',
        ],
        'processing' => [
            'name' => 'กำลังดำเนินการ',
            'color' => 'blue',
            'icon' => '🔄',
        ],
        'completed' => [
            'name' => 'สำเร็จ',
            'color' => 'green',
            'icon' => '✅',
        ],
        'used' => [
            'name' => 'ใช้งานแล้ว',
            'color' => 'purple',
            'icon' => '✓',
        ],
        'expired' => [
            'name' => 'หมดอายุ',
            'color' => 'gray',
            'icon' => '⌛',
        ],
        'cancelled' => [
            'name' => 'ยกเลิก',
            'color' => 'red',
            'icon' => '❌',
        ],
        'refunded' => [
            'name' => 'คืนเงิน',
            'color' => 'orange',
            'icon' => '↩️',
        ],
    ];

    /**
     * ฟิลด์ที่อนุญาตให้ mass assign
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'price_coins',
        'total_coins',
        'discount_coins',
        'product_name',
        'product_type',
        'product_value',
        'product_value_unit',
        'order_number',
        'redemption_code',
        'tracking_number',
        'status',
        'status_note',
        'used_at',
        'used_note',
        'used_location',
        'used_data',
        'expires_at',
        'shipping_address',
        'shipped_at',
        'delivered_at',
        'meta_data',
        'admin_note',
        'user_note',
        'refunded_at',
        'refunded_coins',
        'refund_reason',
        'refunded_by',
        'processed_by',
    ];

    /**
     * ฟิลด์ที่ต้อง cast
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_coins' => 'decimal:2',
        'total_coins' => 'decimal:2',
        'discount_coins' => 'decimal:2',
        'product_value' => 'decimal:2',
        'refunded_coins' => 'decimal:2',
        'used_data' => 'array',
        'shipping_address' => 'array',
        'meta_data' => 'array',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity' => 1,
        'status' => 'pending',
        'discount_coins' => 0,
    ];

    /**
     * Boot model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order_number)) {
                $model->order_number = static::generateOrderNumber();
            }
        });
    }

    // ==========================================
    // ความสัมพันธ์ (Relationships)
    // ==========================================

    /**
     * ผู้ซื้อ
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * สินค้า
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(CoinShopProduct::class, 'product_id');
    }

    /**
     * Admin ผู้ดำเนินการ
     *
     * @return BelongsTo
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Admin ผู้คืนเงิน
     *
     * @return BelongsTo
     */
    public function refunder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * ข้อมูลสถานะ
     *
     * @return array
     */
    public function getStatusInfoAttribute(): array
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['pending'];
    }

    /**
     * ชื่อสถานะ
     *
     * @return string
     */
    public function getStatusNameAttribute(): string
    {
        return $this->status_info['name'];
    }

    /**
     * สีสถานะ
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status_info['color'];
    }

    /**
     * ไอคอนสถานะ
     *
     * @return string
     */
    public function getStatusIconAttribute(): string
    {
        return $this->status_info['icon'];
    }

    /**
     * หมดอายุหรือยัง
     *
     * @return bool
     */
    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * ใช้งานได้หรือไม่
     *
     * @return bool
     */
    public function getIsUsableAttribute(): bool
    {
        return $this->status === 'completed'
            && !$this->is_expired;
    }

    /**
     * คืนเงินได้หรือไม่
     *
     * @return bool
     */
    public function getIsRefundableAttribute(): bool
    {
        // คืนเงินได้เฉพาะสถานะ pending, processing, completed
        return in_array($this->status, ['pending', 'processing', 'completed']);
    }

    /**
     * ข้อมูลประเภทสินค้า
     *
     * @return array
     */
    public function getProductTypeInfoAttribute(): array
    {
        return CoinShopProduct::TYPES[$this->product_type] ?? CoinShopProduct::TYPES['other'];
    }

    /**
     * วันที่ซื้อ (ภาษาไทย)
     *
     * @return string
     */
    public function getPurchaseDateThaiAttribute(): string
    {
        return $this->created_at->thaidate('j M y H:i');
    }

    /**
     * วันหมดอายุ (ภาษาไทย)
     *
     * @return string|null
     */
    public function getExpiryDateThaiAttribute(): ?string
    {
        return $this->expires_at?->thaidate('j M y');
    }

    /**
     * ราคาสุทธิ
     *
     * @return float
     */
    public function getNetPriceAttribute(): float
    {
        return $this->total_coins - $this->discount_coins;
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * กรองตามสถานะ
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * เฉพาะที่รอดำเนินการ
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * เฉพาะที่สำเร็จ
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * เฉพาะที่ใช้งานได้
     */
    public function scopeUsable($query)
    {
        return $query->where('status', 'completed')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * เฉพาะที่หมดอายุ
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNotIn('status', ['used', 'cancelled', 'refunded', 'expired']);
    }

    /**
     * เฉพาะของผู้ใช้
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * เฉพาะของสินค้า
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * เรียงตามล่าสุด
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ==========================================
    // Methods
    // ==========================================

    /**
     * สร้างเลขที่ใบสั่งซื้อ
     *
     * @return string
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'VC' . date('Ymd');
        $random = strtoupper(Str::random(6));
        return $prefix . $random;
    }

    /**
     * สร้างรหัสใช้งาน
     *
     * @return string
     */
    public static function generateRedemptionCode(): string
    {
        return strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
    }

    /**
     * ทำเครื่องหมายว่าใช้งานแล้ว
     *
     * @param string|null $note หมายเหตุ
     * @param string|null $location สถานที่
     * @param array|null $data ข้อมูลเพิ่มเติม
     * @return bool
     */
    public function markAsUsed(?string $note = null, ?string $location = null, ?array $data = null): bool
    {
        if (!$this->is_usable) {
            return false;
        }

        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'used_note' => $note,
            'used_location' => $location,
            'used_data' => $data,
        ]);

        return true;
    }

    /**
     * ทำเครื่องหมายว่าหมดอายุ
     *
     * @return bool
     */
    public function markAsExpired(): bool
    {
        if (!$this->is_expired) {
            return false;
        }

        $this->update([
            'status' => 'expired',
            'status_note' => 'หมดอายุโดยอัตโนมัติ',
        ]);

        return true;
    }

    /**
     * ยกเลิกการสั่งซื้อ
     *
     * @param string|null $reason เหตุผล
     * @return bool
     */
    public function cancel(?string $reason = null): bool
    {
        if (!in_array($this->status, ['pending', 'processing'])) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'status_note' => $reason ?? 'ยกเลิกโดยผู้ใช้',
        ]);

        // คืนสต็อกให้สินค้า
        if ($this->product) {
            $this->product->increaseStock($this->quantity);
        }

        return true;
    }

    /**
     * คืนเงิน
     *
     * @param int $adminId Admin ผู้คืนเงิน
     * @param string|null $reason เหตุผล
     * @param float|null $amount จำนวนที่คืน (null = คืนทั้งหมด)
     * @return bool
     */
    public function refund(int $adminId, ?string $reason = null, ?float $amount = null): bool
    {
        if (!$this->is_refundable) {
            return false;
        }

        $refundAmount = $amount ?? $this->net_price;

        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refunded_coins' => $refundAmount,
            'refund_reason' => $reason,
            'refunded_by' => $adminId,
        ]);

        // คืน Coins ให้ผู้ใช้
        $videoCoin = $this->user->videoCoin;
        if ($videoCoin) {
            $videoCoin->addBalance($refundAmount, 'คืน Coins จากการคืนสินค้า #' . $this->order_number);
        }

        // คืนสต็อกให้สินค้า
        if ($this->product) {
            $this->product->increaseStock($this->quantity);
        }

        return true;
    }

    /**
     * อัพเดทสถานะ
     *
     * @param string $status สถานะใหม่
     * @param string|null $note หมายเหตุ
     * @param int|null $adminId Admin ผู้ดำเนินการ
     * @return bool
     */
    public function updateStatus(string $status, ?string $note = null, ?int $adminId = null): bool
    {
        if (!isset(self::STATUSES[$status])) {
            return false;
        }

        $this->update([
            'status' => $status,
            'status_note' => $note,
            'processed_by' => $adminId,
        ]);

        return true;
    }

    /**
     * ตั้งค่าข้อมูลจัดส่ง
     *
     * @param string $trackingNumber เลขพัสดุ
     * @param int|null $adminId
     * @return bool
     */
    public function setShipped(string $trackingNumber, ?int $adminId = null): bool
    {
        $this->update([
            'tracking_number' => $trackingNumber,
            'shipped_at' => now(),
            'status' => 'completed',
            'status_note' => 'จัดส่งแล้ว',
            'processed_by' => $adminId,
        ]);

        return true;
    }

    /**
     * ตั้งค่าได้รับสินค้าแล้ว
     *
     * @return bool
     */
    public function setDelivered(): bool
    {
        $this->update([
            'delivered_at' => now(),
            'status_note' => 'ได้รับสินค้าแล้ว',
        ]);

        return true;
    }
}
