<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AiGenPromotion Model
 *
 * จัดการโปรโมชั่นสำหรับระบบ AI Gen
 *
 * @property int $id
 * @property string $name ชื่อโปรโมชั่น
 * @property string|null $description คำอธิบาย
 * @property string $type ประเภท (discount_percent, discount_fixed, free_credits, bonus_credits)
 * @property float $value ค่าโปรโมชั่น
 * @property string|null $code โค้ดโปรโมชั่น
 * @property string $applies_to ใช้กับอะไร (all, image, video)
 * @property int|null $max_uses จำนวนครั้งสูงสุด
 * @property int|null $max_uses_per_user จำนวนครั้งสูงสุดต่อผู้ใช้
 * @property int $used_count จำนวนที่ใช้ไปแล้ว
 * @property Carbon|null $starts_at เริ่มเมื่อ
 * @property Carbon|null $expires_at หมดอายุเมื่อ
 * @property float|null $min_wallet_balance ยอด wallet ขั้นต่ำ
 * @property int|null $provider_id Provider เฉพาะ
 * @property bool $is_active สถานะใช้งาน
 */
class AiGenPromotion extends Model
{
    /**
     * @var string
     */
    protected $table = 'ai_gen_promotions';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'code',
        'applies_to',
        'max_uses',
        'max_uses_per_user',
        'used_count',
        'starts_at',
        'expires_at',
        'min_wallet_balance',
        'provider_id',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'min_wallet_balance' => 'decimal:2',
        'max_uses' => 'integer',
        'max_uses_per_user' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiGenProvider::class, 'provider_id');
    }

    /**
     * ตรวจสอบว่าโปรโมชั่นยังใช้ได้หรือไม่
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบว่าผู้ใช้ใช้โปรโมชั่นนี้ได้หรือไม่
     */
    public function canBeUsedBy(User $user): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        // ตรวจสอบจำนวนครั้งที่ผู้ใช้ใช้ไปแล้ว
        if ($this->max_uses_per_user !== null) {
            $userUsageCount = AiGenUsageLog::where('user_id', $user->id)
                ->whereJsonContains('parameters->promotion_id', $this->id)
                ->count();

            if ($userUsageCount >= $this->max_uses_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * คำนวณส่วนลด
     *
     * @param float $originalCost ราคาเดิม
     * @return float ราคาหลังหักส่วนลด
     */
    public function calculateDiscountedCost(float $originalCost): float
    {
        return match ($this->type) {
            'discount_percent' => max(0, $originalCost * (1 - $this->value / 100)),
            'discount_fixed' => max(0, $originalCost - $this->value),
            'free_credits' => 0,
            default => $originalCost,
        };
    }

    /**
     * เพิ่มจำนวนการใช้
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Scope: โปรโมชั่นที่ใช้งานได้
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            });
    }

    /**
     * Scope: กรองตามประเภท
     */
    public function scopeForType($query, string $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('applies_to', 'all')->orWhere('applies_to', $type);
        });
    }
}
