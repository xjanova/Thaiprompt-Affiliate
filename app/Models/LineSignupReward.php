<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\SoftDeletes; // TODO: Uncomment after running migration 2025_11_23_150001

/**
 * รางวัลการสมัครสมาชิก
 *
 * กำหนดรางวัลที่ผู้ใช้จะได้รับเมื่อสมัครสมาชิกผ่าน LINE OA
 * รองรับรางวัลหลายประเภท และเงื่อนไขต่างๆ
 *
 * @property int $id
 * @property string $name ชื่อรางวัล
 * @property string|null $description คำอธิบาย
 * @property string $signup_type ประเภทการสมัคร (free, package, both)
 * @property array|null $package_ids IDs ของแพคเกจ
 * @property string $reward_type ประเภทรางวัล
 * @property float $amount จำนวน
 * @property string|null $coupon_code รหัสคูปอง
 * @property int|null $coupon_template_id Template คูปอง
 * @property array|null $benefit_data ข้อมูลสิทธิพิเศษ
 * @property int|null $product_id สินค้าที่แถม
 * @property string|null $icon Icon
 * @property string $badge_color สี Badge
 * @property int $display_order ลำดับแสดงผล
 * @property bool $is_time_limited จำกัดเวลา?
 * @property \Carbon\Carbon|null $start_date วันเริ่มต้น
 * @property \Carbon\Carbon|null $end_date วันสิ้นสุด
 * @property bool $is_active เปิดใช้งาน?
 * @property bool $is_stackable รับได้หลายรางวัล?
 * @property bool $notify_user แจ้งเตือน?
 * @property string|null $notification_message ข้อความแจ้งเตือน
 * @property int $total_claimed จำนวนที่รับแล้ว
 * @property int|null $max_claims จำนวนสูงสุด
 */
class LineSignupReward extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'signup_type',
        'package_ids',
        'reward_type',
        'amount',
        'coupon_code',
        'coupon_template_id',
        'benefit_data',
        'product_id',
        'icon',
        'badge_color',
        'display_order',
        'is_time_limited',
        'start_date',
        'end_date',
        'is_active',
        'is_stackable',
        'notify_user',
        'notification_message',
        'total_claimed',
        'max_claims',
    ];

    protected $casts = [
        'package_ids' => 'array',
        'benefit_data' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_time_limited' => 'boolean',
        'is_active' => 'boolean',
        'is_stackable' => 'boolean',
        'notify_user' => 'boolean',
        'amount' => 'decimal:2',
    ];

    /**
     * ความสัมพันธ์กับ CouponTemplate
     */
    public function couponTemplate(): BelongsTo
    {
        return $this->belongsTo(CouponTemplate::class);
    }

    /**
     * ความสัมพันธ์กับ Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ความสัมพันธ์กับ Claims
     */
    public function claims(): HasMany
    {
        return $this->hasMany(LineSignupRewardClaim::class, 'reward_id');
    }

    /**
     * ตรวจสอบว่ารางวัลนี้ใช้งานได้หรือไม่
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // ตรวจสอบจำนวนสูงสุด
        if ($this->max_claims && $this->total_claimed >= $this->max_claims) {
            return false;
        }

        // ตรวจสอบช่วงเวลา
        if ($this->is_time_limited) {
            $now = now();
            if ($this->start_date && $now->lt($this->start_date)) {
                return false;
            }
            if ($this->end_date && $now->gt($this->end_date)) {
                return false;
            }
        }

        return true;
    }

    /**
     * ตรวจสอบว่ารางวัลนี้ใช้ได้กับแพคเกจหรือไม่
     */
    public function isValidForPackage(?int $packageId): bool
    {
        // ถ้าเป็น free signup และไม่มี package
        if ($this->signup_type === 'free' && !$packageId) {
            return true;
        }

        // ถ้าเป็น package signup และมี package
        if ($this->signup_type === 'package' && $packageId) {
            if (empty($this->package_ids)) {
                return true; // ทุกแพคเกจ
            }
            return in_array($packageId, $this->package_ids);
        }

        // ถ้าเป็น both
        if ($this->signup_type === 'both') {
            if (!$packageId) {
                return true; // free
            }
            if (empty($this->package_ids)) {
                return true; // ทุกแพคเกจ
            }
            return in_array($packageId, $this->package_ids);
        }

        return false;
    }

    /**
     * ดึงรางวัลที่ใช้ได้สำหรับการสมัคร
     */
    public static function getAvailableRewards(?int $packageId = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('is_time_limited', false)
                    ->orWhere(function ($q) {
                        $q->where('is_time_limited', true)
                            ->where(function ($q2) {
                                $q2->whereNull('start_date')
                                    ->orWhere('start_date', '<=', now());
                            })
                            ->where(function ($q2) {
                                $q2->whereNull('end_date')
                                    ->orWhere('end_date', '>=', now());
                            });
                    });
            })
            ->where(function ($query) {
                $query->whereNull('max_claims')
                    ->orWhereRaw('total_claimed < max_claims');
            })
            ->orderBy('display_order')
            ->orderBy('created_at')
            ->get()
            ->filter(function ($reward) use ($packageId) {
                return $reward->isValidForPackage($packageId);
            });
    }

    /**
     * ดึงข้อความแสดงรางวัล
     */
    public function getDisplayText(): string
    {
        switch ($this->reward_type) {
            case 'wallet_points':
                return number_format($this->amount) . ' แต้ม';
            case 'tpix_tokens':
                return number_format($this->amount, 2) . ' TPIX';
            case 'coupon':
                return 'คูปองส่วนลด';
            case 'rank_points':
                return '+' . number_format($this->amount) . ' คะแนน Rank';
            case 'special_benefit':
                return $this->name;
            case 'bonus_item':
                return 'ของแถม: ' . ($this->product->name ?? 'สินค้า');
            case 'free_downlines':
                return 'ดาวน์ไลน์ฟรี ' . number_format($this->amount) . ' คน';
            case 'experience_points':
                return '+' . number_format($this->amount) . ' XP';
            default:
                return $this->name;
        }
    }

    /**
     * ดึง Icon HTML
     */
    public function getIconHtml(): string
    {
        $defaultIcons = [
            'wallet_points' => 'fa-wallet',
            'tpix_tokens' => 'fa-coins',
            'coupon' => 'fa-ticket-alt',
            'rank_points' => 'fa-star',
            'special_benefit' => 'fa-crown',
            'bonus_item' => 'fa-gift',
            'free_downlines' => 'fa-users',
            'experience_points' => 'fa-trophy',
        ];

        $icon = $this->icon ?? ($defaultIcons[$this->reward_type] ?? 'fa-gift');

        return '<i class="fas ' . $icon . '"></i>';
    }
}
