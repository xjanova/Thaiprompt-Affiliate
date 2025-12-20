<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserCoupon Model
 *
 * คูปองที่ผู้ใช้เก็บไว้
 *
 * @property int $id
 * @property int $user_id
 * @property int $coupon_id
 * @property bool $is_used
 * @property \Carbon\Carbon|null $used_at
 *
 * @property-read User $user
 * @property-read Coupon $coupon
 */
class UserCoupon extends Model
{
    use HasFactory;

    protected $table = 'user_coupons';

    protected $fillable = [
        'user_id',
        'coupon_id',
        'is_used',
        'used_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Coupon
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * เก็บคูปองสำหรับผู้ใช้
     *
     * @param int $userId
     * @param int $couponId
     * @return static|null
     */
    public static function collectCoupon(int $userId, int $couponId): ?static
    {
        // ตรวจสอบว่าเก็บแล้วหรือยัง
        $existing = static::where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->first();

        if ($existing) {
            return null;
        }

        // ตรวจสอบว่าคูปองยังใช้งานได้
        $coupon = Coupon::find($couponId);
        if (!$coupon || !$coupon->isValid()) {
            return null;
        }

        return static::create([
            'user_id' => $userId,
            'coupon_id' => $couponId,
            'is_used' => false,
        ]);
    }

    /**
     * ใช้คูปอง
     *
     * @return bool
     */
    public function markAsUsed(): bool
    {
        $this->is_used = true;
        $this->used_at = now();
        return $this->save();
    }

    /**
     * Scope สำหรับคูปองที่ยังไม่ใช้
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    /**
     * Scope สำหรับคูปองที่ใช้แล้ว
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    /**
     * ดึงคูปองที่ยังใช้งานได้ของผู้ใช้
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAvailableCoupons(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->where('is_used', false)
            ->with(['coupon' => function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            }])
            ->get()
            ->filter(fn($uc) => $uc->coupon !== null);
    }
}
