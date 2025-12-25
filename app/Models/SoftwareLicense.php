<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SoftwareLicense Model
 *
 * จัดการ License Keys สำหรับซอฟต์แวร์
 *
 * @property int $id
 * @property int $software_product_id
 * @property int $user_id
 * @property int|null $order_id
 * @property string $license_key
 * @property string|null $activation_code
 * @property string $status
 * @property bool $is_activated
 * @property \Carbon\Carbon|null $activated_at
 * @property string|null $activated_device
 * @property string|null $activated_ip
 * @property \Carbon\Carbon|null $expires_at
 * @property int $max_activations
 * @property int $activation_count
 * @property int $max_downloads
 * @property int $download_count
 * @property \Carbon\Carbon|null $last_downloaded_at
 * @property array|null $metadata
 * @property string|null $notes
 * @property \Carbon\Carbon|null $revoked_at
 * @property int|null $revoked_by
 * @property string|null $revocation_reason
 */
class SoftwareLicense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'software_licenses';

    protected $fillable = [
        'software_product_id',
        'user_id',
        'order_id',
        'license_key',
        'activation_code',
        'status',
        'is_activated',
        'activated_at',
        'activated_device',
        'activated_ip',
        'expires_at',
        'max_activations',
        'activation_count',
        'max_downloads',
        'download_count',
        'last_downloaded_at',
        'metadata',
        'notes',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
    ];

    protected $casts = [
        'is_activated' => 'boolean',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
        'max_activations' => 'integer',
        'activation_count' => 'integer',
        'max_downloads' => 'integer',
        'download_count' => 'integer',
    ];

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REVOKED = 'revoked';

    /**
     * ความสัมพันธ์กับ SoftwareProduct
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class, 'software_product_id');
    }

    /**
     * ความสัมพันธ์กับ User (ผู้ซื้อ)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * ความสัมพันธ์กับ User (ผู้ยกเลิก)
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * ความสัมพันธ์กับ Downloads
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(SoftwareDownload::class);
    }

    /**
     * Scope: Active licenses
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope: Expired licenses
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * ตรวจสอบว่า license ยังใช้งานได้หรือไม่
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบว่าสามารถดาวน์โหลดได้หรือไม่
     */
    public function canDownload(): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // ไม่จำกัดจำนวนดาวน์โหลด
        if ($this->max_downloads === -1) {
            return true;
        }

        return $this->download_count < $this->max_downloads;
    }

    /**
     * ตรวจสอบว่าสามารถ activate ได้หรือไม่
     */
    public function canActivate(): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        return $this->activation_count < $this->max_activations;
    }

    /**
     * Activate license
     */
    public function activate(string $device, string $ip): bool
    {
        if (!$this->canActivate()) {
            return false;
        }

        $this->update([
            'is_activated' => true,
            'activated_at' => now(),
            'activated_device' => $device,
            'activated_ip' => $ip,
            'activation_count' => $this->activation_count + 1,
        ]);

        return true;
    }

    /**
     * Revoke license
     */
    public function revoke(int $revokedBy, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REVOKED,
            'revoked_at' => now(),
            'revoked_by' => $revokedBy,
            'revocation_reason' => $reason,
        ]);
    }

    /**
     * เพิ่มจำนวนดาวน์โหลด
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    /**
     * ตรวจสอบว่าหมดอายุหรือยัง
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Auto-update status หากหมดอายุ
     */
    public function checkExpiration(): void
    {
        if ($this->isExpired() && $this->status === self::STATUS_ACTIVE) {
            $this->update(['status' => self::STATUS_EXPIRED]);
        }
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => '<span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-sm font-bold">✅ ใช้งานได้</span>',
            self::STATUS_INACTIVE => '<span class="px-3 py-1 bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300 rounded-full text-sm font-bold">⏸️ ไม่ได้ใช้งาน</span>',
            self::STATUS_EXPIRED => '<span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full text-sm font-bold">⏰ หมดอายุ</span>',
            self::STATUS_REVOKED => '<span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full text-sm font-bold">🚫 ถูกยกเลิก</span>',
            default => '<span class="px-3 py-1 bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300 rounded-full text-sm font-bold">❓ ไม่ทราบ</span>',
        };
    }
}
