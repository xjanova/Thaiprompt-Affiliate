<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OfficialShopWarning Model
 *
 * แจ้งเตือนร้านค้าก่อนถอดสินค้าจาก Official Shop
 * - ให้เวลา 3 วันปรับปรุงคะแนน
 * - หากไม่ปรับปรุงจะถูกถอดอัตโนมัติ
 *
 * @property int $id
 * @property int $official_shop_product_id
 * @property int $product_id
 * @property int $store_id
 * @property string $status
 * @property float $current_score
 * @property float $required_score
 * @property string|null $warning_message
 * @property \Carbon\Carbon $warned_at
 * @property \Carbon\Carbon $deadline_at
 * @property \Carbon\Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property string|null $resolution_note
 */
class OfficialShopWarning extends Model
{
    /**
     * ตารางที่ใช้
     *
     * @var string
     */
    protected $table = 'official_shop_warnings';

    /**
     * ฟิลด์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'official_shop_product_id',
        'product_id',
        'store_id',
        'status',
        'current_score',
        'required_score',
        'warning_message',
        'warned_at',
        'deadline_at',
        'resolved_at',
        'resolved_by',
        'resolution_note',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_score' => 'decimal:2',
        'required_score' => 'decimal:2',
        'warned_at' => 'datetime',
        'deadline_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * สถานะ
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IMPROVED = 'improved';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REMOVED = 'removed';

    // ===== Relationships =====

    /**
     * รายการใน Official Shop
     *
     * @return BelongsTo
     */
    public function officialShopProduct(): BelongsTo
    {
        return $this->belongsTo(OfficialShopProduct::class);
    }

    /**
     * สินค้า
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ร้านค้า
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Admin ที่ดำเนินการ
     *
     * @return BelongsTo
     */
    public function resolvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ===== Scopes =====

    /**
     * รอปรับปรุง
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * หมดเวลาแล้ว
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('deadline_at', '<', now());
    }

    /**
     * ยังไม่หมดเวลา
     */
    public function scopeNotExpired($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('deadline_at', '>=', now());
    }

    // ===== Static Methods =====

    /**
     * สร้างการเตือนใหม่
     *
     * @param OfficialShopProduct $ospProduct
     * @param float $currentScore
     * @param float $requiredScore
     * @param int $warningDays จำนวนวันให้ปรับปรุง (default 3)
     * @return self
     */
    public static function createWarning(
        OfficialShopProduct $ospProduct,
        float $currentScore,
        float $requiredScore,
        int $warningDays = 3
    ): self {
        // ตรวจสอบว่ามี warning ที่ยังรอดำเนินการอยู่หรือไม่
        $existingWarning = self::where('official_shop_product_id', $ospProduct->id)
            ->pending()
            ->first();

        if ($existingWarning) {
            return $existingWarning;
        }

        $scoreDiff = $requiredScore - $currentScore;
        $message = "สินค้าของท่านมีคะแนน {$currentScore} คะแนน ต่ำกว่าเกณฑ์ขั้นต่ำ {$requiredScore} คะแนน ";
        $message .= "กรุณาปรับปรุงให้ได้คะแนนเพิ่มอีก {$scoreDiff} คะแนน ภายใน {$warningDays} วัน ";
        $message .= "มิฉะนั้นสินค้าจะถูกถอดออกจาก Official Shop โดยอัตโนมัติ";

        return self::create([
            'official_shop_product_id' => $ospProduct->id,
            'product_id' => $ospProduct->product_id,
            'store_id' => $ospProduct->store_id,
            'status' => self::STATUS_PENDING,
            'current_score' => $currentScore,
            'required_score' => $requiredScore,
            'warning_message' => $message,
            'warned_at' => now(),
            'deadline_at' => now()->addDays($warningDays),
        ]);
    }

    /**
     * ปรับปรุงสำเร็จ
     *
     * @param float $newScore
     * @return bool
     */
    public function markAsImproved(float $newScore): bool
    {
        if ($newScore >= $this->required_score) {
            return $this->update([
                'status' => self::STATUS_IMPROVED,
                'resolved_at' => now(),
                'resolution_note' => "คะแนนปรับปรุงเป็น {$newScore} (ผ่านเกณฑ์ {$this->required_score})",
            ]);
        }

        return false;
    }

    /**
     * หมดเวลาปรับปรุง
     *
     * @return bool
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
            'resolved_at' => now(),
            'resolution_note' => 'หมดเวลาปรับปรุง - สินค้าถูกถอดจาก Official Shop',
        ]);
    }

    /**
     * ถูกถอดออกแล้ว
     *
     * @param int|null $adminId
     * @param string|null $note
     * @return bool
     */
    public function markAsRemoved(?int $adminId = null, ?string $note = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REMOVED,
            'resolved_at' => now(),
            'resolved_by' => $adminId,
            'resolution_note' => $note ?? 'ถูกถอดออกจาก Official Shop',
        ]);
    }

    // ===== Accessors =====

    /**
     * เวลาคงเหลือ
     *
     * @return string
     */
    public function getTimeRemainingAttribute(): string
    {
        if ($this->status !== self::STATUS_PENDING) {
            return 'ดำเนินการแล้ว';
        }

        if ($this->deadline_at->isPast()) {
            return 'หมดเวลาแล้ว';
        }

        return $this->deadline_at->diffForHumans();
    }

    /**
     * สถานะแสดงผล
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอปรับปรุง',
            self::STATUS_IMPROVED => 'ปรับปรุงแล้ว',
            self::STATUS_EXPIRED => 'หมดเวลา',
            self::STATUS_REMOVED => 'ถูกถอดแล้ว',
            default => $this->status,
        };
    }

    /**
     * สีสถานะ
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_IMPROVED => 'green',
            self::STATUS_EXPIRED => 'red',
            self::STATUS_REMOVED => 'gray',
            default => 'gray',
        };
    }
}
