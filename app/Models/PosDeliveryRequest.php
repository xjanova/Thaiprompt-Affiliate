<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * คำขอชำระเงิน + ส่งด้วยไรเดอร์ จากเครื่อง POS (POS → ไรเดอร์ Thai Prompt)
 *
 * วงจร: pending → paid (ลูกค้าจ่ายจากกระเป๋าเงินสำเร็จ ได้ order_id)
 *              → cancelled (แคชเชียร์ยกเลิก) / expired (เกิน 15 นาที — เปลี่ยนตอนอ่าน)
 *
 * ⚠️ token คือสิทธิ์ของลูกค้า (ใครถือ QR ก็เปิดดู/จ่ายได้) — ห้าม log
 * ⚠️ customer_phone ห้ามส่งกลับใน API และห้าม log (ซ่อนไว้ใน $hidden)
 *
 * @property int $id
 * @property string $token
 * @property int $pos_terminal_id
 * @property int $store_id
 * @property string $local_id
 * @property string|null $order_local_id
 * @property array $items [{product_id, sku, name, qty, price}]
 * @property string $subtotal
 * @property string|null $customer_phone
 * @property int|null $target_user_id
 * @property string|null $note
 * @property string $status pending|paid|expired|cancelled
 * @property int|null $order_id
 * @property int|null $paid_by_user_id
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class PosDeliveryRequest extends Model
{
    /** รอลูกค้าสแกนจ่าย */
    public const STATUS_PENDING = 'pending';

    /** ลูกค้าจ่ายแล้ว (มีออเดอร์) */
    public const STATUS_PAID = 'paid';

    /** เกินเวลา 15 นาที */
    public const STATUS_EXPIRED = 'expired';

    /** แคชเชียร์ยกเลิก */
    public const STATUS_CANCELLED = 'cancelled';

    /** อายุ QR (นาที) */
    public const TTL_MINUTES = 15;

    /** ความยาวโทเคน (Str::random) */
    public const TOKEN_LENGTH = 40;

    /** คำนำหน้าใน QR */
    public const QR_PREFIX = 'TPPOS1.';

    protected $table = 'pos_delivery_requests';

    protected $fillable = [
        'token',
        'pos_terminal_id',
        'store_id',
        'local_id',
        'order_local_id',
        'items',
        'subtotal',
        'customer_phone',
        'target_user_id',
        'note',
        'status',
        'order_id',
        'paid_by_user_id',
        'paid_at',
        'cancelled_at',
        'expires_at',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * ห้ามหลุดออกไปกับ toArray()/JSON โดยไม่ตั้งใจ
     */
    protected $hidden = [
        'token',
        'customer_phone',
    ];

    // =========================================
    // ความสัมพันธ์
    // =========================================

    /**
     * เครื่อง POS ที่สร้างคำขอ
     */
    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'pos_terminal_id');
    }

    /**
     * ร้านเจ้าของสินค้า
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * ออเดอร์ที่เกิดจากการจ่าย
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * ผู้ใช้ที่เบอร์โทรตรง (ได้รับ push)
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * ผู้ใช้ที่จ่ายเงิน
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * เฉพาะคำขอที่ยังรอจ่าย (ยังไม่ดูเวลาหมดอายุ — ใช้ isExpired() ประกอบ)
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // =========================================
    // ตัวช่วย
    // =========================================

    /**
     * หมดเวลาแล้วหรือยัง (นับเฉพาะคำขอที่ยัง pending — จ่าย/ยกเลิกแล้วไม่นับว่าหมดอายุ)
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->status === self::STATUS_PENDING
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    /**
     * ยังจ่ายได้หรือไม่ (pending + ไม่หมดเวลา)
     */
    public function isPayable(): bool
    {
        return $this->status === self::STATUS_PENDING && ! $this->isExpired();
    }

    /**
     * ข้อความใน QR
     */
    public function qrPayload(): string
    {
        return self::QR_PREFIX.$this->token;
    }

    /**
     * ตัดคำนำหน้า TPPOS1. ออก แล้วตรวจรูปแบบ (ไม่ผ่าน = null ไม่ต้องค้นฐานข้อมูล)
     */
    public static function normalizeToken(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if (str_starts_with($raw, self::QR_PREFIX)) {
            $raw = substr($raw, strlen(self::QR_PREFIX));
        }

        return preg_match('/^[A-Za-z0-9]{'.self::TOKEN_LENGTH.'}$/', $raw) === 1 ? $raw : null;
    }
}
