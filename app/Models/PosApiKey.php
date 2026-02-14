<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * POS API Key Model
 *
 * เก็บ API Keys ที่ Server สร้างให้ POS
 * Admin จะเห็น key ใน Admin Panel และต้องให้กับลูกค้าเอง
 * ลูกค้าจะนำ API Key ไปกรอกที่ POS เพื่อยืนยันสิทธิ์
 *
 * @property int $id
 * @property int|null $shop_id
 * @property string $key
 * @property string|null $name
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_blocked
 * @property string|null $blocked_reason
 * @property \Carbon\Carbon|null $blocked_at
 * @property int|null $blocked_by
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $expires_at
 * @property array|null $permissions
 * @property int $rate_limit
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class PosApiKey extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตาราง
     */
    protected $table = 'pos_api_keys';

    /**
     * คอลัมน์ที่สามารถกรอกได้
     */
    protected $fillable = [
        'shop_id',
        'key',
        'name',
        'description',
        'is_active',
        'is_blocked',
        'blocked_reason',
        'blocked_at',
        'blocked_by',
        'last_used_at',
        'expires_at',
        'permissions',
        'rate_limit',
    ];

    /**
     * Attribute Casting
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_blocked' => 'boolean',
        'blocked_at' => 'datetime',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'permissions' => 'array',
        'rate_limit' => 'integer',
    ];

    /**
     * Hidden attributes
     */
    protected $hidden = [
        // API Key ไม่ซ่อน เพราะ Admin ต้องเห็น
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * ร้านค้าที่ API Key นี้ผูกอยู่
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'shop_id');
    }

    /**
     * Admin ที่บล็อก API Key นี้
     */
    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * POS Terminals ที่ใช้ API Key นี้
     */
    public function terminals(): HasMany
    {
        return $this->hasMany(PosTerminal::class, 'api_key_id');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * เฉพาะ API Keys ที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * เฉพาะ API Keys ที่ถูกบล็อก
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * เฉพาะ API Keys ที่ใช้งานได้
     */
    public function scopeUsable($query)
    {
        return $query->where('is_active', true)
            ->where('is_blocked', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
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
     * สร้าง API Key ใหม่
     */
    public static function generateKey(): string
    {
        return 'pk_'.Str::random(32);
    }

    /**
     * ตรวจสอบว่า API Key ใช้งานได้หรือไม่
     */
    public function isUsable(): bool
    {
        // ต้อง active
        if (! $this->is_active) {
            return false;
        }

        // ต้องไม่ถูกบล็อก
        if ($this->is_blocked) {
            return false;
        }

        // ต้องไม่หมดอายุ
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * ดึงสถานะเป็นข้อความ
     */
    public function getStatusText(): string
    {
        if (! $this->is_active) {
            return 'ปิดใช้งาน';
        }

        if ($this->is_blocked) {
            return 'ถูกบล็อก';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'หมดอายุ';
        }

        return 'ใช้งานได้';
    }

    /**
     * ดึงสถานะเป็น badge class (Tailwind)
     */
    public function getStatusBadgeClass(): string
    {
        if (! $this->is_active) {
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        }

        if ($this->is_blocked) {
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        }

        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    }

    /**
     * บล็อก API Key
     */
    public function block(string $reason, int $blockedBy): void
    {
        $this->update([
            'is_blocked' => true,
            'blocked_reason' => $reason,
            'blocked_at' => now(),
            'blocked_by' => $blockedBy,
        ]);
    }

    /**
     * ปลดบล็อก API Key
     */
    public function unblock(): void
    {
        $this->update([
            'is_blocked' => false,
            'blocked_reason' => null,
            'blocked_at' => null,
            'blocked_by' => null,
        ]);
    }

    /**
     * อัพเดทเวลาใช้งานล่าสุด
     */
    public function touch(): bool
    {
        return $this->update(['last_used_at' => now()]);
    }

    /**
     * ตรวจสอบสิทธิ์
     */
    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true; // ถ้าไม่กำหนด = มีทุกสิทธิ์
        }

        return in_array($permission, $this->permissions);
    }

    // =========================================
    // Boot Method
    // =========================================

    protected static function boot()
    {
        parent::boot();

        // สร้าง API Key อัตโนมัติถ้าไม่มี
        static::creating(function ($model) {
            if (empty($model->key)) {
                $model->key = self::generateKey();
            }
        });
    }
}
