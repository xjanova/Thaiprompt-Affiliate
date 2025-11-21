<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Affiliate Model
 *
 * Model สำหรับจัดการโครงสร้าง Affiliate/MLM
 *
 * @property int $id
 * @property int $user_id Foreign key ไปยัง users table
 * @property int|null $parent_id Foreign key ไปยัง affiliates table (sponsor)
 * @property string $referral_code รหัสแนะนำ (unique)
 * @property int $level ระดับในโครงสร้าง
 * @property int $total_referrals จำนวนผู้ที่แนะนำทั้งหมด
 * @property float $total_earnings รายได้รวมทั้งหมด
 * @property string $status สถานะ (active, inactive, suspended)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Affiliate extends Model
{
    use HasFactory;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'affiliates';

    /**
     * ฟิลด์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'parent_id',
        'referral_code',
        'level',
        'total_referrals',
        'total_earnings',
        'status',
    ];

    /**
     * การแปลงชนิดข้อมูลของฟิลด์
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'total_referrals' => 'integer',
            'total_earnings' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Affiliate (sponsor/parent)
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'parent_id');
    }

    /**
     * ความสัมพันธ์กับ Affiliates ที่แนะนำมา (children/downline)
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Affiliate::class, 'parent_id');
    }

    /**
     * ความสัมพันธ์กับ Commissions
     *
     * @return HasMany
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Scope สำหรับ affiliate ที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope สำหรับ affiliate ที่ inactive
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope สำหรับ affiliate ที่ suspended
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * ตรวจสอบว่า affiliate เป็น active หรือไม่
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * เพิ่มจำนวนผู้แนะนำ
     *
     * @param int $count จำนวนที่ต้องการเพิ่ม
     * @return bool
     */
    public function incrementReferrals(int $count = 1): bool
    {
        return $this->increment('total_referrals', $count);
    }

    /**
     * เพิ่มรายได้รวม
     *
     * @param float $amount จำนวนเงินที่ต้องการเพิ่ม
     * @return bool
     */
    public function incrementEarnings(float $amount): bool
    {
        return $this->increment('total_earnings', $amount);
    }

    /**
     * ดึงข้อมูล downline ทั้งหมด (ทุกระดับ)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllDownline()
    {
        $downline = collect();

        foreach ($this->children as $child) {
            $downline->push($child);
            $downline = $downline->merge($child->getAllDownline());
        }

        return $downline;
    }

    /**
     * นับจำนวน downline ทั้งหมด
     *
     * @return int
     */
    public function countAllDownline(): int
    {
        return $this->getAllDownline()->count();
    }

    /**
     * สร้างรหัสแนะนำแบบ unique
     *
     * @param string $prefix คำนำหน้า (default: 'REF')
     * @param int $length ความยาว (default: 8)
     * @return string
     */
    public static function generateReferralCode(string $prefix = 'REF', int $length = 8): string
    {
        do {
            $code = $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }
}
