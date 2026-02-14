<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * POS Terminal Model
 *
 * เก็บข้อมูล POS ที่ลงทะเบียนเข้ามา
 * - เมื่อ POS ลงทะเบียน จะสร้าง record ใหม่พร้อม API Key
 * - Admin ต้องให้ API Key กับลูกค้า
 * - ลูกค้ากรอก API Key ที่ POS เพื่อยืนยัน
 *
 * @property int $id
 * @property int|null $shop_id
 * @property int|null $api_key_id
 * @property string $product_key
 * @property string $device_id
 * @property string|null $device_name
 * @property string|null $device_model
 * @property string|null $platform
 * @property string|null $app_version
 * @property string|null $terminal_name
 * @property string $status
 * @property bool $is_verified
 * @property \Carbon\Carbon|null $verified_at
 * @property \Carbon\Carbon|null $last_sync_at
 * @property string|null $last_ip_address
 * @property string|null $notes
 * @property array|null $device_info
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class PosTerminal extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตาราง
     */
    protected $table = 'pos_terminals';

    /**
     * คอลัมน์ที่สามารถกรอกได้
     */
    protected $fillable = [
        'shop_id',
        'api_key_id',
        'product_key',
        'device_id',
        'device_name',
        'device_model',
        'platform',
        'app_version',
        'terminal_name',
        'status',
        'is_verified',
        'verified_at',
        'last_sync_at',
        'last_ip_address',
        'notes',
        'device_info',
    ];

    /**
     * Attribute Casting
     */
    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'device_info' => 'array',
    ];

    /**
     * สถานะที่เป็นไปได้
     */
    public const STATUS_PENDING = 'pending';      // รอกรอก API Key

    public const STATUS_ACTIVE = 'active';        // ใช้งานปกติ

    public const STATUS_SUSPENDED = 'suspended';  // ระงับชั่วคราว

    public const STATUS_BLOCKED = 'blocked';      // บล็อกถาวร

    // =========================================
    // Relationships
    // =========================================

    /**
     * ร้านค้าที่ POS นี้ผูกอยู่
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'shop_id');
    }

    /**
     * API Key ที่ใช้งาน
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(PosApiKey::class, 'api_key_id');
    }

    /**
     * รายงานประจำวัน
     */
    public function dailyReports(): HasMany
    {
        return $this->hasMany(PosDailyReport::class, 'terminal_id');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * เฉพาะ Terminal ที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * เฉพาะ Terminal ที่รอยืนยัน
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * เฉพาะ Terminal ที่ยืนยันแล้ว
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * ค้นหาตาม shop_id
     */
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * ตรวจสอบว่า Terminal สามารถ sync ได้หรือไม่
     */
    public function canSync(): bool
    {
        // ต้อง active
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        // ต้องยืนยันแล้ว
        if (! $this->is_verified) {
            return false;
        }

        // ต้องมี API Key และใช้งานได้
        if (! $this->apiKey || ! $this->apiKey->isUsable()) {
            return false;
        }

        return true;
    }

    /**
     * ดึงสถานะเป็นข้อความ
     */
    public function getStatusText(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอยืนยัน',
            self::STATUS_ACTIVE => $this->is_verified ? 'ใช้งาน' : 'รอกรอก API Key',
            self::STATUS_SUSPENDED => 'ระงับชั่วคราว',
            self::STATUS_BLOCKED => 'ถูกบล็อก',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * ดึงสถานะเป็น badge class (Tailwind)
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            self::STATUS_ACTIVE => $this->is_verified
                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            self::STATUS_SUSPENDED => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            self::STATUS_BLOCKED => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    /**
     * ยืนยัน Terminal ด้วย API Key
     */
    public function verify(PosApiKey $apiKey): bool
    {
        // ตรวจสอบว่า API Key ใช้งานได้
        if (! $apiKey->isUsable()) {
            return false;
        }

        // ตรวจสอบว่า shop_id ตรงกัน (ถ้ามี)
        if ($this->shop_id && $apiKey->shop_id && $this->shop_id !== $apiKey->shop_id) {
            return false;
        }

        // อัพเดท Terminal
        $this->update([
            'api_key_id' => $apiKey->id,
            'shop_id' => $apiKey->shop_id ?? $this->shop_id,
            'status' => self::STATUS_ACTIVE,
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return true;
    }

    /**
     * บล็อก Terminal
     */
    public function block(): void
    {
        $this->update([
            'status' => self::STATUS_BLOCKED,
        ]);
    }

    /**
     * ระงับ Terminal ชั่วคราว
     */
    public function suspend(): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
        ]);
    }

    /**
     * เปิดใช้งาน Terminal
     */
    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * อัพเดทข้อมูล sync
     */
    public function recordSync(?string $ipAddress = null): void
    {
        $this->update([
            'last_sync_at' => now(),
            'last_ip_address' => $ipAddress ?? $this->last_ip_address,
        ]);

        // อัพเดท API Key ด้วย
        if ($this->apiKey) {
            $this->apiKey->touch();
        }
    }

    /**
     * ดึงข้อความแจ้งเตือนถ้า API Key ถูกบล็อก
     */
    public function getBlockedMessage(): ?string
    {
        // ตรวจสอบ Terminal status
        if ($this->status === self::STATUS_BLOCKED) {
            return 'POS Terminal นี้ถูกบล็อก กรุณาติดต่อ Admin';
        }

        if ($this->status === self::STATUS_SUSPENDED) {
            return 'POS Terminal นี้ถูกระงับชั่วคราว กรุณาติดต่อ Admin';
        }

        // ตรวจสอบ API Key
        if ($this->apiKey && $this->apiKey->is_blocked) {
            $reason = $this->apiKey->blocked_reason ?? 'ไม่ระบุเหตุผล';

            return "API Key ถูกบล็อก: {$reason}\nกรุณาติดต่อ Admin เพื่อขอ API Key ใหม่";
        }

        return null;
    }
}
