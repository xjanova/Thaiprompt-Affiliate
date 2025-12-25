<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DeveloperProfile Model
 *
 * โมเดลสำหรับจัดการข้อมูลนักพัฒนาซอฟต์แวร์
 *
 * @property int $id
 * @property int $user_id
 * @property string $display_name ชื่อที่แสดง
 * @property string|null $company_name ชื่อบริษัท
 * @property string|null $bio ประวัติ/คำอธิบาย
 * @property string|null $website เว็บไซต์
 * @property string|null $avatar_url รูปโปรไฟล์
 * @property string|null $facebook_profile โปรไฟล์ Facebook
 * @property string|null $line_id LINE ID
 * @property string|null $phone เบอร์โทรศัพท์
 * @property string $email อีเมล
 * @property string|null $company_registration_number เลขทะเบียนบริษัท
 * @property string|null $tax_id เลขประจำตัวผู้เสียภาษี
 * @property string|null $company_address ที่อยู่บริษัท
 * @property string|null $id_card_image รูปบัตรประชาชน
 * @property string|null $company_registration_doc เอกสารทะเบียนบริษัท
 * @property string $status สถานะ (pending, approved, rejected, suspended)
 * @property string|null $rejection_reason เหตุผลที่ปฏิเสธ
 * @property \Carbon\Carbon|null $approved_at วันที่อนุมัติ
 * @property int|null $approved_by ผู้อนุมัติ (Admin)
 * @property float $commission_rate เปอร์เซ็นต์ค่าคอมมิชชั่น
 * @property int $total_products จำนวนสินค้าทั้งหมด
 * @property int $total_sales จำนวนการขายทั้งหมด
 * @property float $total_earnings รายได้รวมทั้งหมด
 * @property bool $is_verified ยืนยัน KYC แล้ว
 * @property bool $is_active เปิดใช้งาน
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read User $user
 * @property-read User|null $approver
 * @property-read \Illuminate\Database\Eloquent\Collection|SoftwareProduct[] $products
 */
class DeveloperProfile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'developer_profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'display_name',
        'company_name',
        'bio',
        'website',
        'avatar_url',
        'facebook_profile',
        'line_id',
        'phone',
        'email',
        'company_registration_number',
        'tax_id',
        'company_address',
        'id_card_image',
        'company_registration_doc',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'commission_rate',
        'total_products',
        'total_sales',
        'total_earnings',
        'is_verified',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approved_at' => 'datetime',
        'commission_rate' => 'decimal:2',
        'total_products' => 'integer',
        'total_sales' => 'integer',
        'total_earnings' => 'decimal:2',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * สถานะที่เป็นไปได้
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * ค่าคอมมิชชั่น default (70%)
     */
    public const DEFAULT_COMMISSION_RATE = 70.00;

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
     * ความสัมพันธ์กับผู้อนุมัติ (Admin)
     *
     * @return BelongsTo
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * ความสัมพันธ์กับ Software Products
     *
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(SoftwareProduct::class, 'developer_id');
    }

    /**
     * Scope: ดึงเฉพาะที่อนุมัติแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope: ดึงเฉพาะที่รออนุมัติ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: ดึงเฉพาะที่เปิดใช้งาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ดึงเฉพาะที่ยืนยัน KYC แล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * ตรวจสอบว่าสถานะเป็น Approved หรือไม่
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * ตรวจสอบว่าสถานะเป็น Pending หรือไม่
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * ตรวจสอบว่าสถานะเป็น Rejected หรือไม่
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * ตรวจสอบว่าสถานะเป็น Suspended หรือไม่
     *
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * ดึง badge สี HTML สำหรับสถานะ
     *
     * @return string
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_APPROVED => '<span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-sm font-bold">✅ อนุมัติแล้ว</span>',
            self::STATUS_PENDING => '<span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-sm font-bold">⏳ รออนุมัติ</span>',
            self::STATUS_REJECTED => '<span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full text-sm font-bold">❌ ปฏิเสธ</span>',
            self::STATUS_SUSPENDED => '<span class="px-3 py-1 bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300 rounded-full text-sm font-bold">⛔ ระงับ</span>',
            default => '<span class="px-3 py-1 bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300 rounded-full text-sm font-bold">❓ ไม่ทราบ</span>',
        };
    }

    /**
     * คำนวณค่าคอมมิชชั่นที่นักพัฒนาได้รับจากยอดขาย
     *
     * @param float $saleAmount ยอดขาย
     * @return float ค่าคอมมิชชั่นที่ได้รับ
     */
    public function calculateCommission(float $saleAmount): float
    {
        return $saleAmount * ($this->commission_rate / 100);
    }

    /**
     * คำนวณค่าคอมมิชชั่นของแพลตฟอร์ม
     *
     * @param float $saleAmount ยอดขาย
     * @return float ค่าคอมมิชชั่นของแพลตฟอร์ม
     */
    public function calculatePlatformCommission(float $saleAmount): float
    {
        $developerCommission = $this->calculateCommission($saleAmount);
        return $saleAmount - $developerCommission;
    }

    /**
     * อัพเดทสถิติ
     *
     * @param int $sales จำนวนการขายที่เพิ่ม
     * @param float $earnings รายได้ที่เพิ่ม
     * @return void
     */
    public function updateStats(int $sales = 1, float $earnings = 0): void
    {
        $this->increment('total_sales', $sales);
        $this->increment('total_earnings', $earnings);
    }
}
